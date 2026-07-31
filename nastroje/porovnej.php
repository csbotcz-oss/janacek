<?php
/**
 * Porovná dvě měření a vypíše, kde se naše verze liší od originálu.
 *
 *   php porovnej.php <original.json> <nase.json> [--vse]
 *
 * Prvky se párují podle textu. Geometrie se hlásí až od rozdílu 3 px, aby
 * výpis nezahltily zaokrouhlovací odchylky.
 */

[$a, $b] = [json_decode(file_get_contents($argv[1]), true), json_decode(file_get_contents($argv[2]), true)];
$vse = in_array('--vse', $argv, true);

// Párujeme podle textu, ne podle značky — sémantiku jsme záměrně srovnali
// (štítky sekcí jsou u nás <p>, ne šest <h1>). Rozdíl ve značce se hlásí zvlášť.
function klic(array $e): string
{
    return mb_strtolower(preg_replace('/\s+/u', ' ', trim($e['text'])));
}

// Text se může opakovat (telefon v kontaktu i v patičce), proto fronta na klíč.
$mapa = [];
foreach ($b as $e) {
    $mapa[klic($e)][] = $e;
}

$SLEDOVANE = [
    'font'     => 'font',
    'velikost' => 'velikost',
    'vaha'     => 'váha',
    'radek'    => 'řádek',
    'barva'    => 'barva',
];

$rozdily = 0;
$chybi   = 0;
$sedi    = 0;

foreach ($a as $orig) {
    $k = klic($orig);

    if (empty($mapa[$k])) {
        // Obrázky mají jiná jména souborů, ty nepárujeme podle názvu.
        if (str_starts_with($orig['text'], '[img]')) continue;
        printf("CHYBÍ    %-6s %s\n", $orig['tag'], mb_substr($orig['text'], 0, 60));
        $chybi++;
        continue;
    }

    $nas = array_shift($mapa[$k]);
    $zmeny = [];

    foreach ($SLEDOVANE as $pole => $popis) {
        if ($orig[$pole] !== $nas[$pole]) {
            $zmeny[] = sprintf('%s: %s → %s', $popis, $orig[$pole], $nas[$pole]);
        }
    }

    foreach (['sirka' => 'šířka', 'vyska' => 'výška'] as $pole => $popis) {
        if (abs($orig[$pole] - $nas[$pole]) >= 3) {
            $zmeny[] = sprintf('%s: %dpx → %dpx', $popis, $orig[$pole], $nas[$pole]);
        }
    }

    if ($zmeny) {
        printf("LIŠÍ SE  %-6s %s\n", $orig['tag'], mb_substr($orig['text'], 0, 60));
        foreach ($zmeny as $z) {
            echo "           · $z\n";
        }
        $rozdily++;
    } else {
        $sedi++;
        if ($vse) {
            printf("OK       %-6s %s\n", $orig['tag'], mb_substr($orig['text'], 0, 60));
        }
    }
}

$navic = 0;
foreach ($mapa as $zbytek) {
    foreach ($zbytek as $e) {
        if (str_starts_with($e['text'], '[img]')) continue;
        printf("NAVÍC    %-6s %s\n", $e['tag'], mb_substr($e['text'], 0, 60));
        $navic++;
    }
}

printf("\n%s\nsedí %d · liší se %d · chybí %d · navíc %d\n",
    str_repeat('-', 60), $sedi, $rozdily, $chybi, $navic);
