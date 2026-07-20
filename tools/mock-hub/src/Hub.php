<?php

declare(strict_types=1);

/**
 * Logica del mock Hub. Ogni metodo pubblico restituisce [statusCode, payload].
 *
 * Le regole implementate qui NON sono invenzioni del mock: derivano da
 * hub-field-contract.md v3.0. Dove il contratto è esplicito, il commento
 * cita la sezione — così l'Hub vero sa cosa deve replicare.
 */
class Hub
{
    private const TOLERANCE_SECONDS = 300;

    private array $products;
    private array $vendors;
    private array $taxonomy;

    public function __construct(private string $dataDir, private string $runtimeDir)
    {
        $this->products = $this->readJson("$dataDir/products.json");
        $this->vendors  = $this->readJson("$dataDir/vendors.json");
        $this->taxonomy = $this->readJson("$dataDir/taxonomy.json");

        if (! is_dir($runtimeDir)) {
            mkdir($runtimeDir, 0777, true);
        }
    }

    // === Autenticazione =================================================

    public function secret(): string
    {
        return getenv('IWEXA_HMAC_SECRET') ?: 'mock-hub-shared-secret';
    }

    public function verifySignature(string $body, array $server): bool
    {
        if (getenv('MOCK_HUB_ALLOW_UNSIGNED') === '1') {
            return true;
        }

        $signature = $server['HTTP_X_IWEXA_SIGNATURE'] ?? '';
        $timestamp = $server['HTTP_X_IWEXA_TIMESTAMP'] ?? '';

        if ($signature === '' || $timestamp === '' || ! is_numeric($timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body . $timestamp, $this->secret()), $signature);
    }

    // === Catalogo =======================================================

    public function listProducts(array $query): array
    {
        $items = $this->products;

        if (! empty($query['vendorCode'])) {
            $items = array_filter($items, fn ($p) => $p['vendorCode'] === $query['vendorCode']);
        }

        if (! empty($query['googleTaxonomyId'])) {
            $id = (int) $query['googleTaxonomyId'];
            $items = array_filter($items, fn ($p) => $p['category']['googleTaxonomyId'] === $id);
        }

        $items    = array_values($items);
        $total    = count($items);
        $page     = max(1, (int) ($query['page'] ?? 1));
        $pageSize = max(1, (int) ($query['pageSize'] ?? 50));

        return [200, [
            'items' => array_slice($items, ($page - 1) * $pageSize, $pageSize),
            'total' => $total,
        ]];
    }

    public function getProduct(string $slug): array
    {
        foreach ($this->products as $p) {
            if ($p['productSlug'] === $slug) {
                return [200, $p];
            }
        }

        return [404, ['error' => 'Product not found', 'productSlug' => $slug]];
    }

    public function getStock(string $externalProductId): array
    {
        foreach ($this->products as $p) {
            if ($p['externalProductId'] === $externalProductId) {
                return [200, [
                    'inStock'  => $p['inStock'],
                    'quantity' => $p['stockQuantity'] ?? null,
                ]];
            }
        }

        return [404, ['error' => 'Product not found', 'externalProductId' => $externalProductId]];
    }

    public function listVendors(): array
    {
        return [200, $this->vendors];
    }

    public function taxonomy(array $query): array
    {
        return [200, [
            'locale' => $query['locale'] ?? $this->taxonomy['locale'],
            'nodes'  => $this->taxonomy['nodes'],
        ]];
    }

    // === Spedizioni =====================================================

    /**
     * Contract §"Free shipping threshold": la soglia si calcola PER VENDOR /
     * PER PACCHETTO, mai sul totale ordine. Gli articoli con alwaysFree=true
     * non concorrono al conteggio della soglia degli altri pacchetti.
     */
    public function shippingQuote(array $query): array
    {
        $country = $query['country'] ?? null;
        $cartRaw = $query['cart'] ?? null;

        if (! $country || ! $cartRaw) {
            return [422, ['error' => 'Parametri country e cart obbligatori']];
        }

        $cart = json_decode($cartRaw, true);
        if (! is_array($cart)) {
            return [422, ['error' => 'cart deve essere un array JSON [{externalProductId, quantity}]']];
        }

        $byVendor = [];

        foreach ($cart as $line) {
            $product = $this->findByExternalId($line['externalProductId'] ?? '');
            if (! $product) {
                return [422, ['error' => 'Prodotto sconosciuto', 'externalProductId' => $line['externalProductId'] ?? null]];
            }

            $code = $product['vendorCode'];
            $byVendor[$code] ??= ['subtotal' => 0.0, 'policy' => $product['shippingPolicy'], 'fulfillment' => $product['fulfillment']];
            $byVendor[$code]['subtotal'] += $product['sellPrice'] * (int) ($line['quantity'] ?? 1);
        }

        $packages = [];

        foreach ($byVendor as $vendorCode => $pkg) {
            $policy    = $pkg['policy'];
            $alwaysFree = (bool) $policy['alwaysFree'];
            $threshold = $policy['freeShippingThreshold'];

            $cost = $alwaysFree ? 0.0 : (float) $policy['cost'];

            // Soglia valutata sul subtotale DEL SINGOLO PACCHETTO
            if (! $alwaysFree && $threshold !== null && $pkg['subtotal'] >= $threshold) {
                $cost = 0.0;
            }

            $packages[] = [
                'vendorCode'            => (string) $vendorCode,
                'cost'                  => round($cost, 2),
                'freeShippingThreshold' => $alwaysFree ? null : $threshold,
                'alwaysFree'            => $alwaysFree,
                'estimatedDelivery'     => $pkg['fulfillment']['deliveryTimeDays'],
            ];
        }

        return [200, ['country' => $country, 'packages' => $packages]];
    }

    // === Ordini =========================================================

    /**
     * Contract §"Stock Management": all'arrivo di un ordine PayPoc, lo stock
     * viene decrementato immediatamente da Iwexa. PayPoc non scrive mai stock.
     */
    public function createOrder(string $body): array
    {
        $data = json_decode($body, true);

        if (! is_array($data)) {
            return [422, ['error' => 'Body JSON non valido']];
        }

        $missing = array_values(array_filter(
            ['userId', 'shippingAddress', 'items', 'totalAgreed', 'paymentMethod', 'paymentToken'],
            fn ($f) => ! isset($data[$f])
        ));

        if ($missing) {
            return [422, ['error' => 'Campi obbligatori mancanti', 'fields' => $missing]];
        }

        if (! is_array($data['items']) || $data['items'] === []) {
            return [422, ['error' => 'items non può essere vuoto']];
        }

        // Verifica disponibilità -> 409 come da OpenAPI
        foreach ($data['items'] as $line) {
            $product = $this->findByExternalId($line['externalProductId'] ?? '');

            if (! $product) {
                return [422, ['error' => 'Prodotto sconosciuto', 'externalProductId' => $line['externalProductId'] ?? null]];
            }

            $requested = (int) ($line['quantity'] ?? 0);

            if (! $product['inStock'] || $requested > (int) ($product['stockQuantity'] ?? 0)) {
                return [409, [
                    'error'             => 'Stock conflict',
                    'externalProductId' => $product['externalProductId'],
                    'requested'         => $requested,
                    'available'         => (int) ($product['stockQuantity'] ?? 0),
                ]];
            }
        }

        // Split per vendor: una spedizione per vendorCode (contract §Order Schema)
        $shipments = [];
        $subtotal  = 0.0;

        foreach ($this->groupByVendor($data['items']) as $vendorCode => $lines) {
            $product = $this->findByExternalId($lines[0]['externalProductId']);

            foreach ($lines as $l) {
                $subtotal += (float) $l['sellPriceSnapshot'] * (int) $l['quantity'];
            }

            $shipments[] = [
                'shipmentId'        => 'SHP-' . strtoupper(bin2hex(random_bytes(4))),
                'vendorCode'        => (string) $vendorCode,
                'type'              => $product['fulfillment']['type'],
                'status'            => 'preparing',
                'estimatedDelivery' => date('Y-m-d', strtotime('+' . $product['fulfillment']['deliveryTimeDays']['max'] . ' days')),
                'items'             => array_map(fn ($l) => [
                    'externalProductId' => $l['externalProductId'],
                    'quantity'          => (int) $l['quantity'],
                ], $lines),
            ];
        }

        [$_, $quote] = $this->shippingQuote([
            'country' => $data['shippingAddress']['country'] ?? 'IT',
            'cart'    => json_encode(array_map(fn ($l) => [
                'externalProductId' => $l['externalProductId'],
                'quantity'          => $l['quantity'],
            ], $data['items'])),
        ]);

        $shippingTotal = array_sum(array_column($quote['packages'] ?? [], 'cost'));
        $walletApplied = (float) ($data['walletApplied'] ?? 0);

        $order = [
            'orderId'   => 'ORD-' . strtoupper(bin2hex(random_bytes(5))),
            'status'    => 'confirmed',
            'shipments' => $shipments,
            'totals'    => [
                'subtotal'      => round($subtotal, 2),
                'shipping'      => round($shippingTotal, 2),
                'walletApplied' => round($walletApplied, 2),
                'total'         => round($subtotal + $shippingTotal - $walletApplied, 2),
            ],
        ];

        $this->saveOrder($order);

        return [201, $order];
    }

    public function getOrder(string $id): array
    {
        $order = $this->loadOrder($id);

        return $order ? [200, $order] : [404, ['error' => 'Order not found', 'orderId' => $id]];
    }

    public function cancelOrder(string $id): array
    {
        $order = $this->loadOrder($id);

        if (! $order) {
            return [404, ['error' => 'Order not found', 'orderId' => $id]];
        }

        if ($order['status'] === 'delivered') {
            return [409, ['error' => 'Un ordine consegnato non può essere annullato', 'orderId' => $id]];
        }

        $order['status'] = 'cancelled';
        $this->saveOrder($order);

        // Contract §Stock Management: alla cancellazione Iwexa ripristina lo stock
        return [200, ['orderId' => $id, 'status' => 'cancelled', 'stockRestored' => true]];
    }

    public function returnOrder(string $id, string $body): array
    {
        $order = $this->loadOrder($id);

        if (! $order) {
            return [404, ['error' => 'Order not found', 'orderId' => $id]];
        }

        $data = json_decode($body, true);

        if (! is_array($data) || empty($data['items'])) {
            return [422, ['error' => 'items obbligatorio']];
        }

        return [200, [
            'orderId'  => $id,
            'returnId' => 'RET-' . strtoupper(bin2hex(random_bytes(4))),
            'status'   => 'return_opened',
            'items'    => $data['items'],
            'reason'   => $data['reason'] ?? null,
        ]];
    }

    // === Webhook in uscita (Hub -> PayPoc) ==============================

    /**
     * Firma un payload webhook con lo stesso schema che il connettore verifica.
     * Usato da bin/send-webhook.php.
     */
    public function signPayload(string $payload, int $timestamp): string
    {
        return hash_hmac('sha256', $payload . $timestamp, $this->secret());
    }

    public function products(): array
    {
        return $this->products;
    }

    // === Interni ========================================================

    private function findByExternalId(string $id): ?array
    {
        foreach ($this->products as $p) {
            if ($p['externalProductId'] === $id) {
                return $p;
            }
        }

        return null;
    }

    private function groupByVendor(array $items): array
    {
        $grouped = [];

        foreach ($items as $line) {
            $product = $this->findByExternalId($line['externalProductId']);
            $grouped[$product['vendorCode']][] = $line;
        }

        return $grouped;
    }

    private function readJson(string $file): array
    {
        return json_decode((string) file_get_contents($file), true) ?? [];
    }

    private function orderFile(string $id): string
    {
        return $this->runtimeDir . '/order-' . preg_replace('/[^A-Za-z0-9\-]/', '', $id) . '.json';
    }

    private function saveOrder(array $order): void
    {
        file_put_contents($this->orderFile($order['orderId']), json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function loadOrder(string $id): ?array
    {
        $file = $this->orderFile($id);

        return is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    }
}
