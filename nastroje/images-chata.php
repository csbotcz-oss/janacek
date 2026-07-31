<?php
/**
 * Připraví obrázky pro web chaty. Zdroje jsou ze Solidpixels v plné kvalitě,
 * my z nich uděláme varianty pro šířky, ve kterých se opravdu zobrazují.
 */

[$src, $dst] = [rtrim($argv[1], '/'), rtrim($argv[2], '/')];
@mkdir($dst, 0755, true);

require __DIR__ . '/orientace.php';

// zdroj => [cíl, šířky, formát]
$plan = [
    'chata-hero2.jpeg'    => ['hero',              [1200, 1920], 'jpg'],
    'chata-stul.webp'     => ['ubytovani',         [500, 1000],  'jpg'],
    '299-chata-hero.jpeg' => ['galerie-1',         [800, 1600],  'jpg'],
    'galerie1.jpg'        => ['galerie-2',         [500, 1000],  'jpg'],
    'chata-kuchy.jpg'     => ['galerie-3',         [500, 1000],  'jpg'],
    'galerie6.jpg'        => ['galerie-4',         [500, 1000],  'jpg'],
    'chata-schody.jpg'    => ['galerie-5',         [500, 1000],  'jpg'],
    'galerie5.jpg'        => ['galerie-6',         [800, 1600],  'jpg'],
    'vybaveni-chaty.jpg'  => ['galerie-7',         [800, 1600],  'jpg'],
    'chata-1.jpg'         => ['galerie-8',         [500, 1000],  'jpg'],
    'chata-venek1.jpeg'   => ['galerie-9',         [500, 1000],  'jpg'],
    'chata-krb.jpg'       => ['vybaveni',          [400, 800],   'jpg'],
    'galerie4.jpg'        => ['aktivity-4',        [500, 1000],  'jpg'],
    'chata-detail.png'    => ['cenik',             [600, 1200],  'png'],
];

// Aktivity 1–3 používají stejné fotky jako jinde, jen v jiném ořezu.
$kopie = [
    'aktivity-1' => 'chata-venek1.jpeg',
    'aktivity-2' => 'chata-hero2.jpeg',
    'aktivity-3' => '299-chata-hero.jpeg',
];
foreach ($kopie as $cil => $zdroj) {
    $plan[$zdroj . '|' . $cil] = [$cil, [500, 1000], 'jpg'];
}

$celkem = 0;

foreach ($plan as $klic => [$base, $sirky, $typ]) {
    $file = str_contains($klic, '|') ? explode('|', $klic)[0] : $klic;
    $path = "$src/$file";

    if (!is_file($path)) {
        fwrite(STDERR, "CHYBÍ: $file\n");
        continue;
    }

    foreach ($sirky as $w) {
        $im = new Imagick($path);
        srovnejOrientaci($im);

        $target = min($w, $im->getImageWidth());
        if ($target !== $im->getImageWidth()) {
            $im->resizeImage($target, 0, Imagick::FILTER_LANCZOS, 1);
        }
        $im->stripImage();

        $suffix = count($sirky) > 1 ? "-$target" : '';

        if ($typ === 'png') {
            $im->setImageFormat('png');
            $out = "$dst/$base$suffix.png";
        } else {
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(82);
            $im->setSamplingFactors(['2x2', '1x1', '1x1']);
            $im->setInterlaceScheme(Imagick::INTERLACE_JPEG);
            $out = "$dst/$base$suffix.jpg";
        }
        $im->writeImage($out);

        $wp = clone $im;
        $wp->setImageFormat('webp');
        $wp->setImageCompressionQuality($typ === 'png' ? 90 : 80);
        $wp->writeImage("$dst/$base$suffix.webp");

        printf("  %-22s %5dpx  %6s / %6s webp\n", $base . $suffix, $target,
            round(filesize($out) / 1024) . 'kB',
            round(filesize("$dst/$base$suffix.webp") / 1024) . 'kB');

        $celkem += filesize($out) + filesize("$dst/$base$suffix.webp");
        $im->clear(); $wp->clear();
    }
}

// Ikony a logo jdou jako SVG beze změny.
foreach (glob("$src/*.svg") as $svg) {
    $jmeno = basename($svg);
    if (str_contains($jmeno, 'madeby')) continue;   // kredit agentury pryč
    copy($svg, "$dst/$jmeno");
    $celkem += filesize($svg);
}

printf("\nCelkem: %s kB\n", round($celkem / 1024));
