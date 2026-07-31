<?php
/**
 * Udělá snímek celé stránky s vynuceným načtením všech obrázků.
 *
 *   php snimek.php <url> <sirka> <vyska> <vystup.png>
 *
 * Bez vynucení se lazy-loadované fotky pod ohybem nenačtou, sekce se srazí
 * a snímek pak neodpovídá tomu, co uvidí návštěvník.
 */

[$url, $sirka, $vyska, $vystup] = [$argv[1], (int) $argv[2], (int) $argv[3], $argv[4]];

$lokalni = str_contains($url, 'cstest.cz');
$docroot = '/www/hosting/cstest.cz/' . (str_contains($url, 'truhlarstvi') ? 'truhlarstvi-suchanek' : 'chata-prasilka');
$jmeno   = '_snimek-' . md5($url . $sirka) . '.html';
$tmp     = $lokalni ? $docroot . '/' . $jmeno : sys_get_temp_dir() . '/' . $jmeno;

$html = file_get_contents($url, false, stream_context_create([
    'http' => ['header' => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0 Safari/537.36\r\n", 'timeout' => 30],
    'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
]));

if ($html === false) {
    fwrite(STDERR, "Nepodařilo se stáhnout $url\n");
    exit(1);
}

$zaklad = preg_replace('#^(https?://[^/]+).*$#', '$1/', $url);

$skript = <<<'JS'
<script>
(function () {
    function nacti() {
        // Solidpixels i WordPress odkládají fotky do data-src / data-srcset.
        document.querySelectorAll('img').forEach(function (i) {
            var s = i.getAttribute('data-src');
            var ss = i.getAttribute('data-srcset');
            if (ss) i.setAttribute('srcset', ss);
            if (s) i.setAttribute('src', s);
            i.removeAttribute('loading');
            i.classList.remove('is-lazy');
        });
        document.querySelectorAll('source[data-srcset]').forEach(function (s) {
            s.setAttribute('srcset', s.getAttribute('data-srcset'));
        });
        // Cookie lišta by překryla patičku.
        document.querySelectorAll('[class*="cookie"], [id*="cookie"]').forEach(function (e) {
            e.style.display = 'none';
        });
    }
    nacti();
    window.addEventListener('load', nacti);
    setTimeout(nacti, 500);
})();
</script>
JS;

$html = preg_replace('#<head([^>]*)>#i', '<head$1><base href="' . $zaklad . '">', $html, 1);
$html = str_replace('</body>', $skript . '</body>', $html);
file_put_contents($tmp, $html);

$cil = $lokalni ? rtrim($zaklad, '/') . '/' . $jmeno : 'file://' . $tmp;

shell_exec(sprintf(
    'timeout 240 chromium --headless --no-sandbox --disable-gpu --disable-dev-shm-usage '
    . '--hide-scrollbars --force-prefers-reduced-motion --window-size=%d,%d '
    . '--virtual-time-budget=40000 --screenshot=%s %s 2>/dev/null',
    $sirka, $vyska, escapeshellarg($vystup), escapeshellarg($cil)
));

if ($lokalni) {
    @unlink($tmp);
}

if (!is_file($vystup)) {
    fwrite(STDERR, "Snímek se nepodařilo pořídit.\n");
    exit(1);
}

$i = getimagesize($vystup);
printf("%s @ %dpx -> %s (%dx%d)\n", $url, $sirka, $vystup, $i[0], $i[1]);
