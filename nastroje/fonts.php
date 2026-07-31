<?php
// Z Google Fonts CSS vytáhne jen latin + latin-ext, stáhne woff2 a přepíše cesty na lokální.
$css = file_get_contents('gf.css');

// rozseká na bloky "/* subset */ @font-face{...}"
preg_match_all('/\/\*\s*([a-z-]+)\s*\*\/\s*(@font-face\s*\{[^}]*\})/i', $css, $m, PREG_SET_ORDER);

$keep = ['latin', 'latin-ext'];
$out = [];
$files = [];

foreach ($m as $blk) {
    [$all, $subset, $face] = $blk;
    if (!in_array($subset, $keep, true)) continue;

    preg_match('/font-family:\s*\'([^\']+)\'/', $face, $ff);
    preg_match('/font-weight:\s*([0-9 ]+)/', $face, $fw);
    preg_match('/url\((https:\/\/[^)]+\.woff2)\)/', $face, $u);
    if (!$u) continue;

    $fam = strtolower(str_replace(' ', '-', $ff[1] ?? 'font'));
    $wt  = trim($fw[1] ?? '400');
    $wt  = str_replace(' ', '-', $wt);
    $name = "$fam-$wt-$subset.woff2";

    $files[$name] = $u[1];
    $face = str_replace($u[1], "../fonts/$name", $face);
    $out[] = "/* $subset */\n" . $face;
}

foreach ($files as $name => $url) {
    if (!file_exists($name)) {
        file_put_contents($name, file_get_contents($url));
        printf("  staženo %-40s %s\n", $name, number_format(filesize($name) / 1024, 1) . ' kB');
    }
}

file_put_contents('fonts.css', implode("\n", $out) . "\n");
echo "\n@font-face bloků: " . count($out) . ", souborů: " . count($files) . "\n";
