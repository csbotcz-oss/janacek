# chata-prasilka

Statický web. Větev `chata-prasilka` repozitáře `csbotcz-oss/janacek`.

| | |
|---|---|
| Testovací | https://chata-prasilka.cstest.cz/ |
| Ostrý (původní) | https://www.chata-prasilka.cz/ |
| Docroot | `/www/hosting/cstest.cz/chata-prasilka` |
| Worktree | `/root/projects/janacek/chata-prasilka` |

Původní web běží na **Solidpixels** (SaaS stavitel webů) — zdrojové kódy
neexistují a exportovat nejdou. Web je proto poskládaný podle vizuální
předlohy a hodnot naměřených v prohlížeči na ostrém webu.

Referenční snapshot: `/root/backup/janacek/2026-07-30/chata-prasilka/`,
podklady v plné kvalitě: `/root/backup/janacek/2026-07-30/assets-full/chata/`

## Struktura

```
index.html                 celá stránka (one-pager s kotvami)
odeslat.php                zpracování kontaktního formuláře
lib/Smtp.php               minimalistický SMTP klient (na serveru není composer)
config.local.php.vzor      vzor konfigurace — zkopírovat do docrootu a doplnit
assets/css/style.css       veškeré styly
assets/js/main.js          menu, lightbox, odeslání formuláře
assets/fonts/              Barlow, self-hosted
assets/img/                fotky ve variantách pro různé šířky (JPEG + WebP) a ikony SVG
dokumenty/                 PDF se zásadami zpracování osobních údajů
```

## Design tokeny

Naměřené na ostrém webu:

| token | hodnota | použití |
|---|---|---|
| oranžová | `#E85805` | akcent, tlačítka, štítky sekcí |
| meruňková | `#FFBD70` | plochy sekcí a patička |
| lišta hlavičky | `rgb(255 189 112 / .7)` | poloprůhledný pruh |
| mátová | `#E5FFF8` | podklad hera pod fotkou |
| text | `#000000` | |
| popisky formuláře | `#3D3B38` | |
| písmo | Barlow 400/600/700 | jediné na celém webu |

Obsah 1230 px, uvnitř odsazení 8 px, sloupce po 16 px (mřížka 12 sloupců).
Pole formuláře jsou pilulky (rádius 50 px).

Velikosti písma: hero 60/700, nadpisy sekcí 40/600, „Rezervace" 48/600,
podnadpisy 23/600, text 17/400, štítek sekce 14/700, menu 17/700,
tlačítka 13/700.

## Deploy

```sh
/root/projects/janacek/deploy.sh chata-prasilka --dry-run   # náhled
/root/projects/janacek/deploy.sh chata-prasilka             # nasazení
```

Deploy zrcadlí větev do docrootu rsyncem s `--delete`. Ruční úpravy přímo
v docrootu tedy při dalším nasazení zmizí. Z rsyncu jsou vyloučené `.env`
a `config.local.php`, aby přežily nasazení.

## Kontaktní formulář

Formulář posílá POST na `odeslat.php`, ten odešle e-mail přes SMTP.
Konfigurace je v `config.local.php` v docrootu — **není v gitu** a deploy
ji nepřepisuje. Dokud chybí, formulář vrací srozumitelnou hlášku s e-mailem,
nespadne.

Povinné je **e-mail**, **zpráva** a **souhlas se zpracováním údajů** —
stejně jako na originále. Antispam: skryté pole na roboty, minimální doba
vyplnění 3 s a limit 5 odeslání za hodinu na IP.

## Vědomé odchylky od originálu

- **Kredit „Made by" v patičce odstraněn** (odkaz na netpromotion.cz) — na
  přání klienta.
- **Cookie lišta se nedělá.** Originál má lištu Solidpixels; náš statický web
  žádné cookies nenastavuje, takže ji nepotřebuje. K doladění.

## Před spuštěním na ostré doméně

- [ ] `robots.txt` — nahradit obsahem `User-agent: *` / `Allow: /` a doplnit `Sitemap:`
- [ ] nastavit `config.local.php` s ostrými SMTP údaji
- [ ] ověřit SPF/DMARC pro odesílací adresu, jinak poptávky spadnou do spamu
- [ ] zkontrolovat `canonical` a `og:url` (míří na ostrou doménu)
