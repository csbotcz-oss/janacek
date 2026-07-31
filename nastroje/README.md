# Nástroje na porovnávání s originálem

Skripty, kterými se přestavěné weby měří proti ostrým originálům. Vznikly
proto, že odhadovat hodnoty z CSS nefunguje — je potřeba měřit vypočítané
styly v prohlížeči.

Vyžadují `chromium` (nainstalovaný přes apt) a PHP s rozšířením Imagick.

## Skripty

| soubor | k čemu |
|---|---|
| `zmer.php <url> <šířka> <ven.json>` | vypíše vypočítané styly a geometrii všech textových prvků |
| `porovnej.php <orig.json> <naše.json>` | spáruje prvky podle textu a vypíše rozdíly |
| `probe.php <url> <šířka> <skript.js>` | spustí na stránce vlastní JS a vrátí, co přiřadí do `VYSLEDEK` |
| `snimek.php <url> <šířka> <výška> <ven.png>` | snímek celé stránky s vynuceným načtením obrázků |
| `vedle-sebe.php <a.png> <b.png> <výška pásu> <předpona>` | poskládá dva snímky vedle sebe po vodorovných pásech |
| `images-chata.php <zdroj> <cíl>` | vygeneruje responzivní varianty obrázků (JPEG + WebP) |
| `orientace.php` | srovnání EXIF orientace, používá ho `images-chata.php` |
| `fonts.php` | z Google Fonts CSS stáhne woff2 (latin + latin-ext) a přepíše cesty |
| `extract.php <html>` | vytáhne z uloženého HTML obsahovou kostru (nadpisy, texty, formulář) |

## Na co si dát pozor

Tyhle věci mě opakovaně vyvedly z omylu — stály hodiny a je snadné na ně
znovu naletět.

**Fonty přes CORS.** `probe.php` a `zmer.php` podstrkují upravenou stránku
do docrootu vlastního webu, ne do `file://`. Z `file://` by prohlížeč odmítl
načíst `@font-face` (Google hlavičku posílá, náš server ne) a měřilo by se
náhradní písmo místo skutečného.

**Minimální šířka okna je 500 px.** Headless Chromium menší okno neudělá,
takže „375px" testy ve skutečnosti běží na 500. Pro mobilní rozsah (≤767)
to stačí, ale není to skutečných 375.

**requestAnimationFrame se nespouští.** Ve virtuálním čase nepřijde ani
jeden snímek, takže vlastní animace přes rAF nikdy nedoběhnou. Testy
interakcí je potřeba psát s `--force-prefers-reduced-motion`, kde jsou
posuny okamžité.

**Lazy-loadované fotky.** Bez vynucení se pod ohybem nenačtou a sekce se
srazí — všechna měření pozic pod ohybem jsou pak nesmysl. `snimek.php` je
vynucuje, ale u Solidpixels (chata) to jeho vlastní skript rozbije a zmizí
hero. U chaty se proto spoléhá na měření pozic, ne na obrázkové porovnání.

**Elementor obaluje texty do `<span>`.** `porovnej.php` páruje podle textu,
takže srovnává jejich vnitřní `<span>` proti našemu tlačítku. Rozdíl bývá
přesně velikost odsazení — není to chyba.

**Měřit vykreslený výsledek, ne deklarované hodnoty.** Elementor definuje
proměnné, které skoro nepoužívá, a Solidpixels přičítá k odsazení sekce
vnitřní okraje. Zaoblení bývá na obalu obrázku, ne na obrázku.
