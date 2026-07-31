<?php
/**
 * Změří vypočítané styly stránky v headless Chromiu.
 *
 *   php zmer.php <url> <sirka> <vystup.json>
 *
 * Stránku stáhne, vloží do ní <base> a měřicí skript, načte lokálně a nechá
 * prohlížeč vypsat pro každý textový blok skutečné hodnoty — font, velikost,
 * výšku řádku, barvu a geometrii. Cross-origin to neřeší, protože DOM je náš.
 */

[$url, $sirka, $vystup] = [$argv[1], (int) $argv[2], $argv[3]];

// Stránku vlastního webu podstrčíme do jeho docrootu, ne do file:// — fonty
// se načítají přes CORS a z file:// by je server odmítl, takže bychom měřili
// fallback místo skutečného písma. Google Fonts hlavičku posílá, takže
// u originálu file:// stačí.
$lokalni = str_contains($url, 'cstest.cz');
$docroot = '/www/hosting/cstest.cz/' . (str_contains($url, 'truhlarstvi') ? 'truhlarstvi-suchanek' : 'chata-prasilka');
$jmeno   = '_zmer-' . md5($url . $sirka) . '.html';
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
window.addEventListener('load', function () {
    // Fonty se dopočítávají asynchronně; bez tohohle bychom měřili fallback.
    document.fonts.ready.then(function () {
        setTimeout(mer, 400);
    });
});

function viditelny(el) {
    var s = getComputedStyle(el);
    if (s.display === 'none' || s.visibility === 'hidden' || Number(s.opacity) === 0) return false;
    var r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0;
}

function vlastniText(el) {
    var t = '';
    for (var i = 0; i < el.childNodes.length; i++) {
        if (el.childNodes[i].nodeType === 3) t += el.childNodes[i].nodeValue;
    }
    return t.replace(/\s+/g, ' ').trim();
}

function mer() {
    var vysledek = [];
    var vse = document.querySelectorAll('body *');

    for (var i = 0; i < vse.length; i++) {
        var el = vse[i];
        if (/^(SCRIPT|STYLE|NOSCRIPT|SVG|PATH|SYMBOL|USE|LINK|META)$/.test(el.tagName)) continue;
        if (!viditelny(el)) continue;

        var text = vlastniText(el);
        var jeObrazek = el.tagName === 'IMG';
        if (text === '' && !jeObrazek) continue;

        var s = getComputedStyle(el);
        var r = el.getBoundingClientRect();

        vysledek.push({
            tag: el.tagName.toLowerCase(),
            text: jeObrazek ? ('[img] ' + (el.currentSrc || el.src).split('/').pop()) : text.slice(0, 80),
            font: s.fontFamily.split(',')[0].replace(/["']/g, ''),
            velikost: s.fontSize,
            vaha: s.fontWeight,
            radek: s.lineHeight,
            barva: s.color,
            pozadi: s.backgroundColor,
            sirka: Math.round(r.width),
            vyska: Math.round(r.height),
            x: Math.round(r.left),
            y: Math.round(r.top + window.scrollY)
        });
    }

    var pre = document.createElement('pre');
    pre.id = 'vysledek-mereni';
    pre.textContent = JSON.stringify(vysledek);
    document.body.appendChild(pre);
}
</script>
JS;

// <base> je nutná, jinak se relativní odkazy na CSS a fonty rozbijí.
$html = preg_replace('#<head([^>]*)>#i', '<head$1><base href="' . $zaklad . '">', $html, 1);
$html = str_replace('</body>', $skript . '</body>', $html);

file_put_contents($tmp, $html);

$prikaz = sprintf(
    'timeout 120 chromium --headless --no-sandbox --disable-gpu --disable-dev-shm-usage '
    . '--hide-scrollbars --window-size=%d,2000 --virtual-time-budget=15000 --dump-dom %s 2>/dev/null',
    $sirka,
    escapeshellarg($lokalni ? rtrim($zaklad, '/') . '/' . $jmeno : 'file://' . $tmp)
);

$dom = shell_exec($prikaz);

// Podstrčený soubor v docrootu za sebou hned uklidíme.
if ($lokalni) {
    @unlink($tmp);
}

if (!preg_match('#<pre id="vysledek-mereni">(.*?)</pre>#s', $dom, $m)) {
    fwrite(STDERR, "Měření se nepodařilo — skript v prohlížeči nedoběhl.\n");
    exit(1);
}

$data = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true);

if (!is_array($data)) {
    fwrite(STDERR, "Nepodařilo se přečíst výsledek měření.\n");
    exit(1);
}

file_put_contents($vystup, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
printf("%s @ %dpx — změřeno %d prvků -> %s\n", $url, $sirka, count($data), $vystup);
