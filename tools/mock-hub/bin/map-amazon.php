<?php

declare(strict_types=1);

/**
 * Mappa un payload Amazon SP-API sullo schema Product del contratto e
 * mostra cosa manca.
 *
 *   php bin/map-amazon.php                              # usa la fixture di esempio
 *   php bin/map-amazon.php percorso/al/payload.json
 *   php bin/map-amazon.php payload.json --json          # solo il Product, per pipe
 */

require __DIR__ . '/../src/AmazonMapper.php';

$file     = null;
$jsonOnly = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--json') {
        $jsonOnly = true;
    } else {
        $file = $arg;
    }
}

$file ??= __DIR__ . '/../data/amazon-sample-B0H83PBXFF.json';

if (! is_file($file)) {
    fwrite(STDERR, "File non trovato: $file\n");
    exit(1);
}

$payload = json_decode((string) file_get_contents($file), true);
$items   = $payload['items'] ?? [];

if (! $items) {
    fwrite(STDERR, "Nessun item nel payload\n");
    exit(1);
}

$mapper = new AmazonMapper();
$result = $mapper->map($items[0]);

if ($jsonOnly) {
    echo json_encode($result['product'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
    exit(0);
}

$g = fn (string $s) => "\033[32m$s\033[0m";
$r = fn (string $s) => "\033[31m$s\033[0m";
$y = fn (string $s) => "\033[33m$s\033[0m";
$b = fn (string $s) => "\033[1m$s\033[0m";

echo $b("\nCampi del contratto ricavabili dal payload Amazon\n");
echo str_repeat('─', 72) . "\n";

foreach ($result['product'] as $field => $value) {
    if ($value === null || $value === []) {
        printf("  %s %-20s %s\n", $r('✗'), $field, $r('— da fornire'));
        continue;
    }

    $shown = is_array($value) ? (isset($value[0]) ? count($value) . ' elementi' : json_encode($value, JSON_UNESCAPED_UNICODE)) : (string) $value;

    if (mb_strlen($shown) > 44) {
        $shown = mb_substr($shown, 0, 41) . '…';
    }

    printf("  %s %-20s %s\n", $g('✓'), $field, $shown);
}

$total   = count($result['product']);
$filled  = count(array_filter($result['product'], fn ($v) => $v !== null && $v !== []));

echo "\n  " . $b("$filled/$total campi coperti da Amazon\n");

echo $b("\nDa fornire (Amazon non li ha)\n");
echo str_repeat('─', 72) . "\n";

foreach ($result['missing'] as $m) {
    [$field, $why] = array_pad(explode(' — ', $m, 2), 2, '');
    printf("  %s %-18s %s\n", $r('•'), $field, $why);
}

if ($result['warnings']) {
    echo $b("\nDati presenti in Amazon che il contratto non prevede\n");
    echo str_repeat('─', 72) . "\n";

    foreach ($result['warnings'] as $w) {
        echo '  ' . $y('⚠') . "  $w\n";
    }
}

echo $b("\nDati aggiuntivi utilizzabili come attributi Bagisto\n");
echo str_repeat('─', 72) . "\n";

foreach ($result['extra'] as $k => $v) {
    $shown = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;

    if (mb_strlen($shown) > 48) {
        $shown = mb_substr($shown, 0, 45) . '…';
    }

    printf("  %-20s %s\n", $k, $shown);
}

echo "\n";
