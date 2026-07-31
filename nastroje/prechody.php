<?php
/**
 * Najde ve dvou snímcích všechny přechody mezi barvami pásů — kontrola vln.
 *
 *   php prechody.php <a.png> <b.png> <x> [barvaA] [barvaB] [výška]
 *
 * Ve svislém sloupci sleduje, kde se barva přepne z jedné na druhou.
 * Ostatní barvy (fotky, text) ignoruje, takže projde i přes obsah.
 * Výchozí dvojice je meruňková a bílá z chaty.
 */

$a = $argv[1];
$b = $argv[2];
$x = (int) $argv[3];
$barvaA = strtoupper($argv[4] ?? 'FFBD70');
$barvaB = strtoupper($argv[5] ?? 'FFFFFF');
$vyska = (int) ($argv[6] ?? 0);

function prechody(string $png, int $x, string $ba, string $bb, int $vyska): array
{
    $im = imagecreatefrompng($png);
    $max = $vyska ?: imagesy($im);
    $out = [];
    $stav = null;

    for ($y = 0; $y < $max; $y++) {
        $c = imagecolorat($im, $x, $y);
        $h = sprintf('%02X%02X%02X', ($c >> 16) & 255, ($c >> 8) & 255, $c & 255);
        $s = $h === $ba ? 'A' : ($h === $bb ? 'B' : null);
        if ($s === null) continue;
        if ($stav !== null && $s !== $stav) $out[] = [$stav . '->' . $s, $y];
        $stav = $s;
    }

    return $out;
}

$pa = prechody($a, $x, $barvaA, $barvaB, $vyska);
$pb = prechody($b, $x, $barvaA, $barvaB, $vyska);

printf("x=%d: %s %d přechodů, %s %d\n", $x, basename($a), count($pa), basename($b), count($pb));

for ($i = 0; $i < max(count($pa), count($pb)); $i++) {
    $u = $pa[$i] ?? null;
    $v = $pb[$i] ?? null;
    $d = '';
    if ($u && $v) $d = $u[0] === $v[0] ? sprintf('%+d', $v[1] - $u[1]) : 'JINÝ SMĚR';
    printf("  %-14s %-14s %s\n",
        $u ? $u[0] . '@' . $u[1] : '—',
        $v ? $v[0] . '@' . $v[1] : '—',
        $d);
}
