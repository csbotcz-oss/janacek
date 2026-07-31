<?php
/**
 * Porovná svislé rozložení textu ve dvou snímcích.
 *
 *   php radky.php <a.png> <b.png> <x1> <x2> <y1> <y2> [tmavy|svetly]
 *
 * V zadaném svislém pruhu najde souvislé bloky řádků, ve kterých je text,
 * a spáruje je za sebou. Tím se pozná, o kolik je obsah posunutý — a hlavně
 * jestli je posun všude stejný (pak stačí opravit odsazení sekce), nebo se
 * mění (pak nesedí i mezery uvnitř).
 *
 * Pruh volit tak, aby v něm nebyly fotky: ve snímku ostrého webu se fotky
 * pod ohybem nenačtou a bloky by se nespárovaly.
 */

[$a, $b, $x1, $x2, $y1, $y2] = [$argv[1], $argv[2], (int) $argv[3], (int) $argv[4], (int) $argv[5], (int) $argv[6]];
$rezim = $argv[7] ?? 'tmavy';

function bloky(string $png, int $x1, int $x2, int $y1, int $y2, string $rezim): array
{
    $im = imagecreatefrompng($png);
    $out = [];
    $od = null;

    for ($y = $y1; $y <= $y2; $y++) {
        $n = 0;
        for ($x = $x1; $x < $x2; $x++) {
            $c = imagecolorat($im, $x, $y);
            $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $bl = $c & 255;
            $je = $rezim === 'tmavy' ? ($r + $g + $bl < 260) : ($r > 205 && $g > 205 && $bl > 205);
            if ($je && ++$n > 2) break;
        }
        if ($n > 2 && $od === null) $od = $y;
        if ($n <= 2 && $od !== null) {
            if ($y - $od > 3) $out[] = [$od, $y - 1];
            $od = null;
        }
    }

    return $out;
}

$ba = bloky($a, $x1, $x2, $y1, $y2, $rezim);
$bb = bloky($b, $x1, $x2, $y1, $y2, $rezim);

printf("bloků: %s %d, %s %d\n", basename($a), count($ba), basename($b), count($bb));

$max = 0;
for ($i = 0; $i < max(count($ba), count($bb)); $i++) {
    $x = $ba[$i] ?? null;
    $y = $bb[$i] ?? null;
    $d = ($x && $y) ? $y[0] - $x[0] : null;
    if ($d !== null) $max = max($max, abs($d));
    printf("  %s   %s   %s\n",
        $x ? sprintf('%5d-%-5d(v%3d)', $x[0], $x[1], $x[1] - $x[0] + 1) : '      —       ',
        $y ? sprintf('%5d-%-5d(v%3d)', $y[0], $y[1], $y[1] - $y[0] + 1) : '      —       ',
        $d !== null ? sprintf('Δ%+d%s', $d, abs($d) > 4 ? '  !' : '') : '');
}

printf("největší odchylka: %d px\n", $max);
