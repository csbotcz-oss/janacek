<?php
/**
 * Spustí na stránce vlastní JS a vrátí, co vyhodnotí.
 *
 *   php probe.php <url> <sirka> <soubor-s-js>
 *
 * JS musí přiřadit výsledek (cokoli serializovatelného) do proměnné VYSLEDEK.
 */

[$url, $sirka, $jsSoubor] = [$argv[1], (int) $argv[2], $argv[3]];

$js = file_get_contents($jsSoubor);
// Stránku vlastního webu podstrčíme do jeho docrootu, ne do file:// — fonty
// se načítají přes CORS a z file:// by je server odmítl, takže bychom měřili
// fallback místo skutečného písma.
$lokalni = str_contains($url, 'cstest.cz');
$docroot = '/www/hosting/cstest.cz/' . (str_contains($url, 'truhlarstvi') ? 'truhlarstvi-suchanek' : 'chata-prasilka');
$jmeno = '_probe-' . md5($url . $js . $sirka) . '.html';
$tmp = $lokalni ? $docroot . '/' . $jmeno : sys_get_temp_dir() . '/' . $jmeno;

$html = file_get_contents($url, false, stream_context_create([
    'http' => ['header' => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0 Safari/537.36\r\n", 'timeout' => 30],
    'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
]));

if ($html === false) {
    fwrite(STDERR, "Nepodařilo se stáhnout $url\n");
    exit(1);
}

$zaklad = preg_replace('#^(https?://[^/]+).*$#', '$1/', $url);

$obal = "<script>window.addEventListener('load',function(){document.fonts.ready.then(function(){setTimeout(function(){\n"
      . "var VYSLEDEK;\ntry{\n" . $js . "\n}catch(e){VYSLEDEK={chyba:String(e)};}\n"
      . "var p=document.createElement('pre');p.id='probe-vysledek';p.textContent=JSON.stringify(VYSLEDEK);document.body.appendChild(p);\n"
      . "},400);});});</script>";

$html = preg_replace('#<head([^>]*)>#i', '<head$1><base href="' . $zaklad . '">', $html, 1);
$html = str_replace('</body>', $obal . '</body>', $html);
file_put_contents($tmp, $html);

$dom = shell_exec(sprintf(
    'timeout 120 chromium --headless --no-sandbox --disable-gpu --disable-dev-shm-usage '
    . '--hide-scrollbars --window-size=%d,2000 --virtual-time-budget=15000 --dump-dom %s 2>/dev/null',
    $sirka, escapeshellarg($lokalni ? rtrim($zaklad, '/') . '/' . $jmeno : 'file://' . $tmp)
));

// Podstrčený soubor v docrootu za sebou hned uklidíme.
if ($lokalni) { @unlink($tmp); }

if (!preg_match('#<pre id="probe-vysledek">(.*?)</pre>#s', $dom, $m)) {
    fwrite(STDERR, "Skript nedoběhl.\n");
    exit(1);
}

echo json_encode(
    json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
), "\n";
