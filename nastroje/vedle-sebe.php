<?php
/**
 * Poskládá originál a naši verzi vedle sebe po vodorovných pásech,
 * aby se daly porovnat po sekcích.
 *
 *   php vedle-sebe.php <orig.png> <nas.png> <vyska-pasu> <predpona>
 */

[$a, $b, $pas, $predpona] = [$argv[1], $argv[2], (int) $argv[3], $argv[4]];

$ia = new Imagick($a);
$ib = new Imagick($b);

$sirka = $ia->getImageWidth();
$vyska = min($ia->getImageHeight(), $ib->getImageHeight());
$pocet = (int) ceil($vyska / $pas);

$popisek = new ImagickDraw();
$popisek->setFillColor('#ffffff');
$popisek->setFontSize(22);

for ($i = 0; $i < $pocet; $i++) {
    $y = $i * $pas;
    $h = min($pas, $vyska - $y);

    $la = clone $ia; $la->cropImage($sirka, $h, 0, $y);
    $lb = clone $ib; $lb->cropImage($sirka, $h, 0, $y);

    // plátno: dva snímky vedle sebe + pruh nahoře na popisky
    $plat = new Imagick();
    $plat->newImage($sirka * 2 + 30, $h + 34, new ImagickPixel('#1b1b1b'));
    $plat->setImageFormat('png');

    $plat->compositeImage($la, Imagick::COMPOSITE_OVER, 0, 34);
    $plat->compositeImage($lb, Imagick::COMPOSITE_OVER, $sirka + 30, 34);

    $plat->annotateImage($popisek, 12, 24, 0, 'ORIGINÁL  (y ' . $y . '–' . ($y + $h) . ')');
    $plat->annotateImage($popisek, $sirka + 42, 24, 0, 'NAŠE VERZE');

    // zmenšit na polovinu, ať se to dá prohlédnout naráz
    $plat->resizeImage((int) ($plat->getImageWidth() / 2), 0, Imagick::FILTER_LANCZOS, 1);

    $soubor = sprintf('%s-%02d.png', $predpona, $i + 1);
    $plat->writeImage($soubor);
    printf("%s  (y %d–%d)\n", $soubor, $y, $y + $h);

    $la->clear(); $lb->clear(); $plat->clear();
}
