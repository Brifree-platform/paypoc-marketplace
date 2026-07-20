<?php

declare(strict_types=1);

/**
 * Traduce un item Amazon SP-API (Catalog Items v2022-04-01) nello schema
 * Product del contratto Iwexa <-> PayPoc v3.0.
 *
 * Il payload Amazon è una sorgente di CONTENUTO (nome, descrizione, immagini,
 * EAN, dimensioni). Non contiene i dati COMMERCIALI e LOGISTICI che il
 * contratto richiede: prezzo di vendita, stock, vendor, fulfillment,
 * politica di spedizione. Quelli sono di Iwexa e vanno aggiunti da lei.
 *
 * Il mapper non inventa nulla: ciò che manca finisce in `missing`.
 */
class AmazonMapper
{
    /** Percentuale di listPrice applicabile come credito (contract §Pricing) */
    private const MAX_APPLICABLE_RATE = 0.40;

    /**
     * Amazon productType -> Google Product Taxonomy.
     *
     * Il productType è la chiave più stabile: è un enum finito e controllato
     * da Amazon, mentre il browse node cambia per marketplace e nel tempo.
     * Il browse node resta utile come raffinamento quando un productType
     * copre più categorie Google.
     */
    private const PRODUCT_TYPE_MAP = [
        'PERSONAL_FRAGRANCE' => [
            'googleTaxonomyId'   => 479,
            'googleTaxonomyPath' => 'Health & Beauty > Personal Care > Cosmetics > Perfume & Cologne',
            'localizedPath'      => ['Salute e bellezza', 'Cura della persona', 'Cosmetici', 'Profumi e colonie'],
        ],
    ];

    public function map(array $item, string $marketplaceId = 'APJ6JRA9NG5V4'): array
    {
        $attr    = $item['attributes'] ?? [];
        $summary = $item['summaries'][0] ?? [];

        $name        = $this->attr($attr, 'item_name') ?? ($summary['itemName'] ?? null);
        $productType = $item['productTypes'][0]['productType'] ?? null;
        $listPrice   = $attr['list_price'][0]['value_with_tax'] ?? null;

        $missing  = [];
        $warnings = [];

        $category = self::PRODUCT_TYPE_MAP[$productType] ?? null;

        if (! $category) {
            $missing[] = "category — nessuna mappatura per productType '$productType'";
        }

        // --- Dati che Amazon non ha e Iwexa deve fornire ------------------
        foreach ([
            'vendorCode'     => 'chiave di routing del contratto, senza la quale l\'ordine non è splittabile',
            'vendorName'     => 'mostrato come "Spedito da {vendorName}"',
            'vendorSlug'     => 'usato per l\'URL /vendor/{vendorSlug}',
            'sellPrice'      => 'Amazon espone solo list_price; il prezzo di vendita è una decisione commerciale di Iwexa',
            'inStock'        => 'la disponibilità arriva da un\'altra API (FBA Inventory), non dal catalogo',
            'fulfillment'    => 'FBI/FBV, magazzino, tempi di preparazione e consegna',
            'shippingPolicy' => 'costo, soglia di spedizione gratuita, alwaysFree per paese',
        ] as $field => $why) {
            $missing[] = "$field — $why";
        }

        if ($listPrice === null) {
            $missing[] = 'listPrice — assente da list_price';
        }

        // vatRate: deducibile dalla categoria merceologica, ma resta una
        // regola fiscale, non un dato del payload. Non lo inventiamo.
        $missing[] = 'vatRate — aliquota IVA: regola fiscale di Iwexa, non presente nel payload Amazon';

        // --- Dati presenti in Amazon che il CONTRATTO non prevede ---------
        $hazmat = $this->hazmatAspects($attr);

        if ($hazmat) {
            $warnings[] = 'hazmat presente (' . ($hazmat['united_nations_regulatory_id'] ?? '?')
                . ', classe ' . ($hazmat['transportation_regulatory_class'] ?? '?')
                . '): merce infiammabile con restrizioni di trasporto. Lo schema Product del contratto non ha un campo per rappresentarla.';
        }

        if ($this->attr($attr, 'ingredients') || $this->attr($attr, 'safety_warning')) {
            $warnings[] = 'ingredienti e avvertenze di sicurezza presenti: informazioni obbligatorie per legge sui cosmetici in UE (GPSR). Lo schema Product del contratto non le prevede.';
        }

        // Decisione 2: l'identità è l'EAN, non l'ASIN. L'ASIN resta come
        // riferimento Amazon, ma non è la chiave del contratto.
        $ean = null;

        foreach ($attr['externally_assigned_product_identifier'] ?? [] as $id) {
            if (strtolower((string) ($id['type'] ?? '')) === 'ean') {
                $ean = (string) $id['value'];
                break;
            }
        }

        $ean ??= $attr['part_number'][0]['value'] ?? null;

        if ($ean === null) {
            $missing[] = 'externalProductId — nessun EAN nel payload (né externally_assigned_product_identifier né part_number)';
        }

        $product = [
            'externalProductId' => $ean,
            'productSlug'       => $name ? $this->slug($name) : null,
            'vendorSlug'        => null,
            'vendorCode'        => null,
            'vendorName'        => null,
            'name'              => $name,
            'description'       => $this->attr($attr, 'product_description'),
            'bullets'           => $this->attrAll($attr, 'bullet_point'),
            'images'            => $this->bestImages($item, $marketplaceId),
            'brand'             => $this->attr($attr, 'brand') ?? ($summary['brand'] ?? null),
            'category'          => $category,
            'listPrice'         => $listPrice !== null ? (float) $listPrice : null,
            'sellPrice'         => null,
            'vatRate'           => null,
            'maxApplicableValue' => $listPrice !== null ? round($listPrice * self::MAX_APPLICABLE_RATE, 2) : null,
            'currency'          => $attr['list_price'][0]['currency'] ?? 'EUR',
            'inStock'           => null,
            'stockQuantity'     => null,
            'fulfillment'       => null,
            'shippingPolicy'    => null,
            // Campi aggiunti al contratto v3.1 dopo l'analisi del payload reale
            'hazmat'            => $this->hazmatContract($attr),
            'compliance'        => array_filter([
                'manufacturer'  => $this->attr($attr, 'manufacturer'),
                'contactEmail'  => $attr['gpsr_manufacturer_reference'][0]['gpsr_manufacturer_email_address'] ?? null,
                'ingredients'   => $this->attr($attr, 'ingredients'),
                'safetyWarning' => $this->attr($attr, 'safety_warning'),
            ], fn ($v) => $v !== null) ?: null,
            'amazonUrl'         => isset($item['asin']) ? 'https://www.amazon.it/dp/' . $item['asin'] : null,
        ];

        return [
            'product'  => $product,
            'missing'  => $missing,
            'warnings' => $warnings,
            'extra'    => $this->extra($attr, $summary),
        ];
    }

    /**
     * Dati utili presenti nel payload Amazon ma fuori dallo schema Product.
     * Vanno da qualche parte: attributi Bagisto, oppure un'estensione del contratto.
     */
    private function extra(array $attr, array $summary): array
    {
        return array_filter([
            'ean'                => $attr['externally_assigned_product_identifier'][0]['value'] ?? null,
            'manufacturer'       => $this->attr($attr, 'manufacturer'),
            'gpsrContactEmail'   => $attr['gpsr_manufacturer_reference'][0]['gpsr_manufacturer_email_address'] ?? null,
            'ingredients'        => $this->attr($attr, 'ingredients'),
            'safetyWarning'      => $this->attr($attr, 'safety_warning'),
            'scent'              => $this->attr($attr, 'scent'),
            'color'              => $this->attr($attr, 'color'),
            'targetGender'       => $attr['target_gender'][0]['value'] ?? null,
            'itemForm'           => $this->attr($attr, 'item_form'),
            'size'               => $this->attr($attr, 'size'),
            'volumeMl'           => $attr['liquid_volume'][0]['value'] ?? null,
            'packageWeightKg'    => $attr['item_package_weight'][0]['value'] ?? null,
            'packageDimensionsCm' => isset($attr['item_package_dimensions'][0])
                ? [
                    'length' => $attr['item_package_dimensions'][0]['length']['value'] ?? null,
                    'width'  => $attr['item_package_dimensions'][0]['width']['value'] ?? null,
                    'height' => $attr['item_package_dimensions'][0]['height']['value'] ?? null,
                ]
                : null,
            'shelfLifeDays'      => $attr['fc_shelf_life'][0]['value'] ?? null,
            'unspscCode'         => $attr['unspsc_code'][0]['value'] ?? null,
            'amazonBrowseNodeId' => $summary['browseClassification']['classificationId'] ?? null,
            'amazonBrowseName'   => $summary['browseClassification']['displayName'] ?? null,
            'hazmat'             => $this->hazmatAspects($attr) ?: null,
        ], fn ($v) => $v !== null);
    }

    /** Hazmat nella forma prevista dal contratto v3.1 */
    private function hazmatContract(array $attr): ?array
    {
        $a = $this->hazmatAspects($attr);

        if (! $a) {
            return null;
        }

        return array_filter([
            'unRegulatoryId'     => $a['united_nations_regulatory_id'] ?? null,
            'properShippingName' => $a['proper_shipping_name'] ?? null,
            'transportClass'     => $a['transportation_regulatory_class'] ?? null,
            'packingGroup'       => $a['regulatory_packing_group'] ?? null,
            'countryExceptions'  => $a['exceptions'] ?? null,
        ], fn ($v) => $v !== null);
    }

    private function hazmatAspects(array $attr): array
    {
        $out = [];

        foreach ($attr['hazmat'] ?? [] as $h) {
            if (($h['aspect'] ?? '') === 'exception') {
                $out['exceptions'][] = $h['value'];
            } elseif (isset($h['aspect'])) {
                $out[$h['aspect']] = $h['value'];
            }
        }

        return $out;
    }

    /** Immagine più grande per ogni variante, MAIN per prima */
    private function bestImages(array $item, string $marketplaceId): array
    {
        $group = null;

        foreach ($item['images'] ?? [] as $g) {
            if (($g['marketplaceId'] ?? null) === $marketplaceId) {
                $group = $g['images'] ?? [];
                break;
            }
        }

        if (! $group) {
            return [];
        }

        $best = [];

        foreach ($group as $img) {
            $variant = $img['variant'] ?? 'MAIN';
            $current = $best[$variant]['width'] ?? -1;

            if (($img['width'] ?? 0) > $current) {
                $best[$variant] = $img;
            }
        }

        uksort($best, fn ($a, $b) => ($a === 'MAIN' ? -1 : ($b === 'MAIN' ? 1 : strcmp($a, $b))));

        return array_values(array_map(fn ($i) => $i['link'], $best));
    }

    private function attr(array $attr, string $key): ?string
    {
        return $attr[$key][0]['value'] ?? null;
    }

    private function attrAll(array $attr, string $key): array
    {
        return array_values(array_map(fn ($e) => $e['value'], $attr[$key] ?? []));
    }

    private function slug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = str_replace(['à', 'è', 'é', 'ì', 'ò', 'ù'], ['a', 'e', 'e', 'i', 'o', 'u'], $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        // Slug leggibile: le prime 8 parole bastano per un URL
        $parts = array_slice(explode('-', $slug), 0, 8);

        return implode('-', $parts);
    }
}
