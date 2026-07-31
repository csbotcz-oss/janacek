<?php
/**
 * Snímek ostrého webu chaty s vypnutou scroll-animací.
 *
 *   php snimek-chata.php <sirka> <vyska> <vystup.png>
 *
 * Solidpixels drží bloky před najetím na `opacity: 0` a odkrývá je až podle
 * pozice při scrollování. V hlavičkovém snímku se část sekcí nikdy neodkryje
 * a v obrázku pak chybí — fotky i texty. Tady se třída `in-viewport` nasadí
 * ručně, takže je vidět všechno.
 *
 * Do načítání fotek se schválně nesahá — jakmile se jim přepíše data-src,
 * rozbije se Solidpixels vlastní skript a rozpadne se celé rozvržení.
 *
 * Stránka se podstrkuje do docrootu chaty na cstest.cz, ne do file://, aby
 * prošly fonty přes CORS. Po dokončení se soubor maže.
 */

[$sirka, $vyska, $vystup] = [(int) $argv[1], (int) $argv[2], $argv[3]];

$url = 'https://www.chata-prasilka.cz/';
$docroot = '/www/hosting/cstest.cz/chata-prasilka';
$jmeno = '_orig-' . $sirka . '.html';

$html = file_get_contents($url, false, stream_context_create([
    'http' => ['header' => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0 Safari/537.36\r\n", 'timeout' => 30],
    'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
]));

if ($html === false) {
    fwrite(STDERR, "Nepodařilo se stáhnout $url\n");
    exit(1);
}

$skript = <<<'JS'
<script>
(function () {
    function odkryj() {
        document.querySelectorAll('.section-body, .row-main, .gallery-item, .fullrow')
            .forEach(function (e) { e.classList.add('in-viewport'); });
        document.querySelectorAll('[class*="cookie"], [id*="cookie"]').forEach(function (e) {
            e.style.display = 'none';
        });
    }
    odkryj();
    window.addEventListener('load', odkryj);
    setTimeout(odkryj, 300);
    setTimeout(odkryj, 1500);
})();
</script>
JS;

$html = preg_replace('#<head([^>]*)>#i', '<head$1><base href="' . $url . '">', $html, 1);
$html = str_replace('</body>', $skript . '</body>', $html);
file_put_contents($docroot . '/' . $jmeno, $html);

shell_exec(sprintf(
    'timeout 240 chromium --headless --no-sandbox --disable-gpu --disable-dev-shm-usage '
    . '--hide-scrollbars --force-prefers-reduced-motion --window-size=%d,%d '
    . '--virtual-time-budget=45000 --screenshot=%s %s 2>/dev/null',
    $sirka, $vyska, escapeshellarg($vystup),
    escapeshellarg('https://chata-prasilka.cstest.cz/' . $jmeno)
));

@unlink($docroot . '/' . $jmeno);

if (!is_file($vystup)) {
    fwrite(STDERR, "Snímek se nepodařilo pořídit.\n");
    exit(1);
}

$i = getimagesize($vystup);
printf("%s @ %dpx -> %s (%dx%d)\n", $url, $sirka, $vystup, $i[0], $i[1]);
