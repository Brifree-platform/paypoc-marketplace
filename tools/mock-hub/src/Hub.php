<?php

declare(strict_types=1);

/**
 * Logica del mock Hub. Ogni metodo pubblico restituisce [statusCode, payload].
 *
 * Implementa le sei decisioni di riconciliazione del 2026-07-20
 * (vedi docs/RICONCILIAZIONE-CONTRATTO.md):
 *
 *  1. Prezzi e IVA: valore SINGOLO, risolto per il paese richiesto via ?country=
 *  2. Identità prodotto: EAN (externalProductId)
 *  3. Iwexa invisibile al cliente: magazzino FBI = PAYPOC-CENTRAL, tracking su dominio PayPoc
 *  4. Autenticazione: Bearer token E firma HMAC con finestra anti-replay
 *  5. Contenuto multi-lingua, risolto per ?locale=
 *  6. Sync incrementale via ?updated_since=
 *
 * Internamente le fixture tengono i dati per paese e per lingua; l'API espone
 * sempre valori già risolti, come previsto dalla decisione 1.
 */
class Hub
{
    private const TOLERANCE_SECONDS = 300;

    public const DEFAULT_COUNTRY = 'IT';

    public const DEFAULT_LOCALE = 'it';

    /** Percentuale di listPrice applicabile come credito (contract §Pricing) */
    private const MAX_APPLICABLE_RATE = 0.40;

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

    // === Autenticazione (decisione 4: la più forte possibile) ============

    public function secret(): string
    {
        return getenv('IWEXA_HMAC_SECRET') ?: 'mock-hub-shared-secret';
    }

    public function apiKey(): string
    {
        return getenv('IWEXA_API_KEY') ?: 'mock-hub-api-key';
    }

    /**
     * @return array{0:bool,1:string} [autorizzato, motivo del rifiuto]
     */
    public function authorize(string $body, array $server): array
    {
        if (getenv('MOCK_HUB_ALLOW_UNSIGNED') === '1') {
            return [true, ''];
        }

        // 1. Bearer token — identifica il chiamante
        $auth = $server['HTTP_AUTHORIZATION'] ?? '';

        if (! preg_match('/^Bearer\s+(.+)$/i', trim($auth), $m)) {
            return [false, 'Missing bearer token'];
        }

        if (! hash_equals($this->apiKey(), trim($m[1]))) {
            return [false, 'Invalid bearer token'];
        }

        // 2. Firma HMAC — garantisce che il corpo non sia stato alterato
        $signature = $server['HTTP_X_IWEXA_SIGNATURE'] ?? '';
        $timestamp = $server['HTTP_X_IWEXA_TIMESTAMP'] ?? '';

        if ($signature === '' || $timestamp === '') {
            return [false, 'Missing signature headers'];
        }

        // 3. Finestra temporale — impedisce il replay di una richiesta catturata
        if (! is_numeric($timestamp) || abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return [false, 'Signature timestamp outside tolerance window'];
        }

        if (! hash_equals(hash_hmac('sha256', $body . $timestamp, $this->secret()), $signature)) {
            return [false, 'Invalid signature'];
        }

        return [true, ''];
    }

    // === Catalogo =======================================================

    public function listProducts(array $query): array
    {
        $country = strtoupper((string) ($query['country'] ?? self::DEFAULT_COUNTRY));
        $locale  = strtolower((string) ($query['locale'] ?? self::DEFAULT_LOCALE));

        $items = $this->products;

        if (! empty($query['vendorCode'])) {
            $items = array_filter($items, fn ($p) => $p['vendorCode'] === $query['vendorCode']);
        }

        if (! empty($query['googleTaxonomyId'])) {
            $id    = (int) $query['googleTaxonomyId'];
            $items = array_filter($items, fn ($p) => $p['category']['googleTaxonomyId'] === $id);
        }

        // Decisione 6: sync incrementale
        if (! empty($query['updated_since'])) {
            $since = strtotime((string) $query['updated_since']);

            if ($since === false) {
                return [422, ['error' => 'updated_since non è una data ISO8601 valida']];
            }

            $items = array_filter($items, fn ($p) => strtotime($p['updatedAt']) > $since);
        }

        $items    = array_values($items);
        $total    = count($items);
        $page     = max(1, (int) ($query['page'] ?? 1));
        $pageSize = max(1, (int) ($query['pageSize'] ?? 50));
        $slice    = array_slice($items, ($page - 1) * $pageSize, $pageSize);

        $projected = [];

        foreach ($slice as $p) {
            $out = $this->project($p, $country, $locale);

            if ($out === null) {
                return [422, ['error' => "Paese non gestito per uno dei prodotti", 'country' => $country]];
            }

            $projected[] = $out;
        }

        return [200, ['items' => $projected, 'total' => $total, 'country' => $country, 'locale' => $locale]];
    }

    /**
     * Accetta indifferentemente EAN (identità, decisione 2) o slug (URL).
     */
    public function getProduct(string $idOrSlug, array $query): array
    {
        $country = strtoupper((string) ($query['country'] ?? self::DEFAULT_COUNTRY));
        $locale  = strtolower((string) ($query['locale'] ?? self::DEFAULT_LOCALE));

        foreach ($this->products as $p) {
            if ($p['externalProductId'] === $idOrSlug || $p['productSlug'] === $idOrSlug) {
                $out = $this->project($p, $country, $locale);

                return $out === null
                    ? [422, ['error' => 'Paese non gestito per questo prodotto', 'country' => $country]]
                    : [200, $out];
            }
        }

        return [404, ['error' => 'Product not found', 'id' => $idOrSlug]];
    }

    public function getStock(string $externalProductId): array
    {
        $p = $this->findByEan($externalProductId);

        return $p
            ? [200, ['externalProductId' => $p['externalProductId'], 'inStock' => $p['inStock'], 'quantity' => $p['stockQuantity'] ?? null]]
            : [404, ['error' => 'Product not found', 'externalProductId' => $externalProductId]];
    }

    public function listVendors(): array
    {
        return [200, $this->vendors];
    }

    public function taxonomy(array $query): array
    {
        $locale = strtolower((string) ($query['locale'] ?? self::DEFAULT_LOCALE));

        $nodes = array_map(fn ($n) => [
            'googleTaxonomyId'   => $n['googleTaxonomyId'],
            'googleTaxonomyPath' => $n['googleTaxonomyPath'],
            'localizedPath'      => $n['localizedPath'][$locale] ?? $n['localizedPath'][self::DEFAULT_LOCALE],
        ], $this->taxonomy['nodes']);

        return [200, ['locale' => $locale, 'nodes' => $nodes]];
    }

    /**
     * Proietta un prodotto interno nello schema del contratto, risolvendo
     * prezzo, IVA, spedizione (per paese) e contenuto (per lingua).
     * Restituisce null se il paese non è coperto.
     */
    private function project(array $p, string $country, string $locale): ?array
    {
        $pricing  = $p['pricing'][$country] ?? null;
        $shipping = $p['shippingPolicy'][$country] ?? null;

        if (! $pricing || ! $shipping) {
            return null;
        }

        $content = $p['i18n'][$locale] ?? $p['i18n'][self::DEFAULT_LOCALE];

        return [
            'externalProductId'  => $p['externalProductId'],
            'productSlug'        => $p['productSlug'],
            'vendorSlug'         => $p['vendorSlug'],
            'vendorCode'         => $p['vendorCode'],
            'vendorName'         => $p['vendorName'],
            'updatedAt'          => $p['updatedAt'],
            'name'               => $content['name'],
            'description'        => $content['description'],
            'bullets'            => $content['bullets'],
            'images'             => $p['images'],
            'brand'              => $p['brand'],
            'category'           => [
                'googleTaxonomyId'   => $p['category']['googleTaxonomyId'],
                'googleTaxonomyPath' => $p['category']['googleTaxonomyPath'],
                'localizedPath'      => $p['category']['localizedPath'][$locale] ?? $p['category']['localizedPath'][self::DEFAULT_LOCALE],
            ],
            'listPrice'          => $pricing['listPrice'],
            'sellPrice'          => $pricing['sellPrice'],
            'vatRate'            => $pricing['vatRate'],
            'maxApplicableValue' => round($pricing['listPrice'] * self::MAX_APPLICABLE_RATE, 2),
            'currency'           => 'EUR',
            'country'            => $country,
            'inStock'            => $p['inStock'],
            'stockQuantity'      => $p['stockQuantity'] ?? null,
            'fulfillment'        => $p['fulfillment'],
            'shippingPolicy'     => [
                'country'               => $country,
                'cost'                  => $shipping['cost'],
                'freeShippingThreshold' => $shipping['freeShippingThreshold'],
                'alwaysFree'            => $shipping['alwaysFree'],
            ],
            'hazmat'             => $p['hazmat'],
            'compliance'         => $p['compliance'],
            'amazonUrl'          => $p['amazonUrl'],
        ];
    }

    // === Spedizioni =====================================================

    /**
     * Contract §"Free shipping threshold": la soglia si calcola PER VENDOR /
     * PER PACCHETTO, mai sul totale ordine. Gli articoli con alwaysFree=true
     * non concorrono al conteggio della soglia degli altri pacchetti.
     */
    public function shippingQuote(array $query): array
    {
        $country = strtoupper((string) ($query['country'] ?? ''));
        $cartRaw = $query['cart'] ?? null;

        if ($country === '' || ! $cartRaw) {
            return [422, ['error' => 'Parametri country e cart obbligatori']];
        }

        $cart = json_decode((string) $cartRaw, true);

        if (! is_array($cart)) {
            return [422, ['error' => 'cart deve essere un array JSON [{externalProductId, quantity}]']];
        }

        $byVendor = [];

        foreach ($cart as $line) {
            $product = $this->findByEan((string) ($line['externalProductId'] ?? ''));

            if (! $product) {
                return [422, ['error' => 'Prodotto sconosciuto', 'externalProductId' => $line['externalProductId'] ?? null]];
            }

            $pricing  = $product['pricing'][$country] ?? null;
            $shipping = $product['shippingPolicy'][$country] ?? null;

            if (! $pricing || ! $shipping) {
                return [422, ['error' => 'Paese non gestito per questo prodotto', 'country' => $country]];
            }

            $code = $product['vendorCode'];
            $byVendor[$code] ??= ['subtotal' => 0.0, 'policy' => $shipping, 'fulfillment' => $product['fulfillment']];
            $byVendor[$code]['subtotal'] += $pricing['sellPrice'] * (int) ($line['quantity'] ?? 1);
        }

        $packages = [];

        foreach ($byVendor as $vendorCode => $pkg) {
            $policy     = $pkg['policy'];
            $alwaysFree = (bool) $policy['alwaysFree'];
            $threshold  = $policy['freeShippingThreshold'];

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
     * viene decrementato immediatamente dall'Hub. PayPoc non scrive mai stock.
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

        $country = strtoupper((string) ($data['shippingAddress']['country'] ?? self::DEFAULT_COUNTRY));

        foreach ($data['items'] as $line) {
            $product = $this->findByEan((string) ($line['externalProductId'] ?? ''));

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

        $shipments = [];
        $subtotal  = 0.0;

        foreach ($this->groupByVendor($data['items']) as $vendorCode => $lines) {
            $product = $this->findByEan($lines[0]['externalProductId']);

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

        [, $quote] = $this->shippingQuote([
            'country' => $country,
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
            'country'   => $country,
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

    public function signPayload(string $payload, int $timestamp): string
    {
        return hash_hmac('sha256', $payload . $timestamp, $this->secret());
    }

    public function products(): array
    {
        return $this->products;
    }

    public function project2(array $p, string $country, string $locale): ?array
    {
        return $this->project($p, $country, $locale);
    }

    // === Interni ========================================================

    private function findByEan(string $ean): ?array
    {
        foreach ($this->products as $p) {
            if ($p['externalProductId'] === $ean) {
                return $p;
            }
        }

        return null;
    }

    private function groupByVendor(array $items): array
    {
        $grouped = [];

        foreach ($items as $line) {
            $product = $this->findByEan($line['externalProductId']);
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
