<?php
// Vytáhne čitelnou obsahovou kostru ze staženého HTML.
$html = file_get_contents($argv[1]);

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
libxml_clear_errors();
$xp = new DOMXPath($doc);

function t($n) { return trim(preg_replace('/\s+/u', ' ', $n->textContent)); }

echo "=== TITLE ===\n";
foreach ($xp->query('//title') as $n) echo t($n), "\n";

echo "\n=== META ===\n";
foreach ($xp->query('//meta[@name="description"]|//meta[@property="og:title"]|//meta[@property="og:description"]|//meta[@property="og:image"]') as $n) {
    echo ($n->getAttribute('name') ?: $n->getAttribute('property')), ': ', $n->getAttribute('content'), "\n";
}

echo "\n=== NADPISY A TEXT (v pořadí) ===\n";
$q = '//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::p or self::li or self::a or self::button or self::label]';
$seen = [];
foreach ($xp->query($q) as $n) {
    // přeskoč skripty/styly a prázdné
    $txt = t($n);
    if ($txt === '' || mb_strlen($txt) > 400) continue;
    // přeskoč uzly, jejichž text už pokryl potomek (dedup podle textu)
    $tag = $n->nodeName;
    $key = $tag . '|' . $txt;
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $extra = '';
    if ($tag === 'a')  $extra = ' -> ' . $n->getAttribute('href');
    printf("%-7s %s%s\n", strtoupper($tag), $txt, $extra);
}

echo "\n=== FORMULÁŘ ===\n";
foreach ($xp->query('//form') as $f) {
    echo 'form action=', $f->getAttribute('action'), ' method=', $f->getAttribute('method'), "\n";
    foreach ($xp->query('.//input|.//textarea|.//select|.//button', $f) as $i) {
        printf("  %-9s name=%-22s type=%-10s placeholder=%s required=%s\n",
            $i->nodeName,
            $i->getAttribute('name'),
            $i->getAttribute('type'),
            $i->getAttribute('placeholder'),
            $i->hasAttribute('required') ? 'ano' : '-');
    }
}
