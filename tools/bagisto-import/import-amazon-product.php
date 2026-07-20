<?php

/**
 * Prototipo: porta un prodotto dal payload Amazon SP-API dentro Bagisto,
 * passando per lo schema Product del contratto v3.0.
 *
 * Va eseguito DALLA cartella dell'app Bagisto:
 *   php /percorso/a/tools/bagisto-import/import-amazon-product.php
 *
 * Dimostra il percorso completo Amazon -> contratto -> Bagisto e rende
 * visibili i punti dove servono dati che Amazon non fornisce.
 */

declare(strict_types=1);

require getcwd() . '/vendor/autoload.php';
$app = require_once getcwd() . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require __DIR__ . '/../mock-hub/src/AmazonMapper.php';

$payloadFile = __DIR__ . '/../mock-hub/data/amazon-sample-B0H83PBXFF.json';
$payload     = json_decode((string) file_get_contents($payloadFile), true);
$mapped      = (new AmazonMapper())->map($payload['items'][0]);

$product = $mapped['product'];
$extra   = $mapped['extra'];

echo "Import di {$product['externalProductId']} — {$product['name']}\n";
echo str_repeat('─', 72) . "\n";

// --- Valori che Amazon non fornisce -------------------------------------
// In produzione arrivano da Iwexa. Qui sono segnaposto ESPLICITI: senza di
// essi il prodotto non è vendibile, e vanno visti come tali.
$placeholder = [
    'sellPrice'     => $product['listPrice'],   // nessuno sconto: sellPrice = listPrice
    'stockQuantity' => 10,
    'vendorCode'    => 'PLACEHOLDER-VENDOR',
];

echo "Segnaposto usati (in produzione vengono da Iwexa):\n";
foreach ($placeholder as $k => $v) {
    echo "  · $k = $v\n";
}
echo "\n";

$categoryRepository = app(\Webkul\Category\Repositories\CategoryRepository::class);
$productRepository  = app(\Webkul\Product\Repositories\ProductRepository::class);

$locale = core()->getDefaultLocaleCodeFromDefaultChannel() ?: 'en';

// --- 1. Albero categorie dal localizedPath del contratto ------------------
// Il contratto impone che i path siano identici fra Iwexa e PayPoc:
// l'albero Bagisto rispecchia esattamente localizedPath, senza rimappature.
$parentId = 1; // Root
$created  = [];

foreach ($product['category']['localizedPath'] as $level) {
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $level) ?? '');
    $slug = trim($slug, '-');

    $existing = \Webkul\Category\Models\Category::where('parent_id', $parentId)
        ->whereHas('translations', fn ($q) => $q->where('slug', $slug))
        ->first();

    if ($existing) {
        $parentId  = $existing->id;
        $created[] = "$level (esistente, id {$existing->id})";
        continue;
    }

    $category = $categoryRepository->create([
        'slug'         => $slug,
        'name'         => $level,
        'parent_id'    => $parentId,
        'status'       => 1,
        'display_mode' => 'products_and_description',
        'locale'       => $locale,
        $locale        => ['slug' => $slug, 'name' => $level, 'description' => ''],
    ]);

    $parentId  = $category->id;
    $created[] = "$level (creata, id {$category->id})";
}

echo "Albero categorie (da localizedPath, nessuna rimappatura):\n";
foreach ($created as $i => $c) {
    echo '  ' . str_repeat('  ', $i) . "└ $c\n";
}
echo "\n";

// --- 2. Prodotto ---------------------------------------------------------
$sku      = $product['externalProductId'];
$existing = \Webkul\Product\Models\Product::where('sku', $sku)->first();

if ($existing) {
    echo "Prodotto già presente (id {$existing->id}), lo rimuovo e lo ricreo\n\n";
    $productRepository->delete($existing->id);
}

$created = $productRepository->create([
    'type'                => 'simple',
    'attribute_family_id' => 1,
    'sku'                 => $sku,
]);

$bullets = '<ul>' . implode('', array_map(fn ($b) => '<li>' . htmlspecialchars($b) . '</li>', $product['bullets'])) . '</ul>';

$descrizione = '<p>' . htmlspecialchars((string) $product['description']) . '</p>' . $bullets
    . '<h4>Ingredienti</h4><p>' . htmlspecialchars((string) ($extra['ingredients'] ?? '')) . '</p>'
    . '<h4>Avvertenze di sicurezza</h4><p>' . htmlspecialchars((string) ($extra['safetyWarning'] ?? '')) . '</p>'
    . '<p><small>Produttore: ' . htmlspecialchars((string) ($extra['manufacturer'] ?? ''))
    . ' — ' . htmlspecialchars((string) ($extra['gpsrContactEmail'] ?? '')) . '</small></p>';

$productRepository->update([
    'channel'              => core()->getCurrentChannelCode(),
    'locale'               => $locale,
    'sku'                  => $sku,
    'name'                 => $product['name'],
    'url_key'              => $product['productSlug'],
    'short_description'    => '<p>' . htmlspecialchars((string) $product['bullets'][0]) . '</p>',
    'description'          => $descrizione,
    'meta_title'           => $product['name'],
    'meta_description'     => mb_substr(strip_tags((string) $product['description']), 0, 155),
    'price'                => $placeholder['sellPrice'],
    'weight'               => $extra['packageWeightKg'] ?? 0.5,
    'status'               => 1,
    'visible_individually' => 1,
    'guest_checkout'       => 1,
    'new'                  => 1,
    'featured'             => 0,
    'categories'           => [$parentId],
    'inventories'          => [1 => $placeholder['stockQuantity']],
], $created->id);

$saved = \Webkul\Product\Models\Product::find($created->id);

echo "Prodotto creato\n";
echo str_repeat('─', 72) . "\n";
printf("  %-22s %s\n", 'id', $saved->id);
printf("  %-22s %s\n", 'sku (= ASIN)', $saved->sku);
printf("  %-22s %s\n", 'nome', mb_substr((string) $saved->name, 0, 46) . '…');
printf("  %-22s %s\n", 'url_key', $saved->url_key);
printf("  %-22s %s %s\n", 'prezzo', $saved->price, core()->getBaseCurrencyCode());
printf("  %-22s %s\n", 'categoria', implode(' > ', $product['category']['localizedPath']));
printf("  %-22s %s\n", 'googleTaxonomyId', $product['category']['googleTaxonomyId']);
printf("  %-22s %s\n", 'quantità', $saved->inventories->sum('qty'));
printf("  %-22s %s\n", 'immagini', count($product['images']) . ' URL disponibili (non scaricate)');

echo "\nDa completare prima che sia vendibile:\n";
foreach ($mapped['missing'] as $m) {
    echo '  · ' . explode(' — ', $m)[0] . "\n";
}
echo "\n";
