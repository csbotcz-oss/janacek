<?php
/**
 * Otočí pixely podle EXIF orientace a teprve pak příznak srovná.
 * Samotné přepsání příznaku obrázek NEotočí — jen prohlížeči zalže.
 */
function srovnejOrientaci(Imagick $im): void
{
    $cerna = new ImagickPixel('#000000');
    switch ($im->getImageOrientation()) {
        case Imagick::ORIENTATION_TOPRIGHT:    $im->flopImage(); break;
        case Imagick::ORIENTATION_BOTTOMRIGHT: $im->rotateImage($cerna, 180); break;
        case Imagick::ORIENTATION_BOTTOMLEFT:  $im->flipImage(); break;
        case Imagick::ORIENTATION_LEFTTOP:     $im->flopImage(); $im->rotateImage($cerna, -90); break;
        case Imagick::ORIENTATION_RIGHTTOP:    $im->rotateImage($cerna, 90); break;
        case Imagick::ORIENTATION_RIGHTBOTTOM: $im->flopImage(); $im->rotateImage($cerna, 90); break;
        case Imagick::ORIENTATION_LEFTBOTTOM:  $im->rotateImage($cerna, -90); break;
    }
    $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
}
