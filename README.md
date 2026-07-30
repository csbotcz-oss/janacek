# truhlarstvi-suchanek

Statický web. Větev `truhlarstvi-suchanek` repozitáře `csbotcz-oss/janacek`.

| | |
|---|---|
| Testovací | https://truhlarstvi-suchanek.cstest.cz/ |
| Ostrý (původní) | https://truhlarstvi-suchanek.cz/ |
| Docroot | `/www/hosting/cstest.cz/truhlarstvi-suchanek` |
| Worktree | `/root/projects/janacek/truhlarstvi-suchanek` |

Původní web běží na WordPressu 6.9 + Elementoru 4.0.2. Přepsán na statické
HTML/CSS — obsah spravuje csbot.cz, klient administraci nepotřebuje.

Referenční snapshot původního webu: `/root/backup/janacek/2026-07-30/truhlarstvi-suchanek/`,
originální obrázky v plné kvalitě: `/root/backup/janacek/2026-07-30/assets-full/truhlarstvi/`

## Struktura

```
index.html                 celá stránka (one-pager s kotvami)
odeslat.php                zpracování kontaktního formuláře
lib/Smtp.php               minimalistický SMTP klient (na serveru není composer)
config.local.php.vzor      vzor konfigurace — zkopírovat do docrootu a doplnit
assets/css/style.css       veškeré styly
assets/js/main.js          menu, karusel, lightbox, animace, odeslání formuláře
assets/fonts/              Montserrat + Roboto Flex + Roboto, self-hosted
assets/img/                obrázky ve variantách pro různé šířky, JPEG + WebP
dokumenty/                 PDF se zásadami zpracování osobních údajů
```

## Design tokeny

Převzaté 1:1 z původního webu (Elementor globals):

| token | hodnota | použití |
|---|---|---|
| primární | `#522E0A` | tmavě hnědá |
| zelená | `#0B8600` | tlačítka, ikony, podtržení |
| krémová | `#EDEAE6` | světlé plochy, hlavička, patička |
| hnědá | `#844606` | doplňková |
| nadpisy | Montserrat 500/600/700 | |
| text | Roboto Flex 400, 18px / 1.8 | |

Šířka obsahu 1320 px, rádius 25 px, zlom tablet 1024 px, mobil 767 px.

## Deploy

```sh
/root/projects/janacek/deploy.sh truhlarstvi-suchanek --dry-run   # náhled
/root/projects/janacek/deploy.sh truhlarstvi-suchanek             # nasazení
```

Deploy zrcadlí větev do docrootu rsyncem s `--delete`. Ruční úpravy přímo
v docrootu tedy při dalším nasazení zmizí. Z rsyncu jsou vyloučené `.env`
a `config.local.php`, aby přežily nasazení.

## Kontaktní formulář

Formulář posílá POST na `odeslat.php`, ten odešle e-mail přes SMTP. Konfigurace
je v `config.local.php` v docrootu — **není v gitu** a deploy ji nepřepisuje.

Nastavení:

```sh
cp config.local.php.vzor /www/hosting/cstest.cz/truhlarstvi-suchanek/config.local.php
# doplnit SMTP host, uživatele a heslo
```

Dokud konfigurace chybí, formulář vrací srozumitelnou hlášku s telefonem
a e-mailem, nespadne.

Antispam bez reCAPTCHY (původní web měl reCAPTCHU od Googlu):
skryté pole na roboty, minimální doba vyplnění 3 s a limit 5 odeslání za
hodinu na IP.

## Před spuštěním na ostré doméně

- [ ] `robots.txt` — nahradit obsahem `User-agent: *` / `Allow: /` a doplnit `Sitemap:`
- [ ] zkontrolovat `canonical` a `og:url` v `index.html` (už míří na ostrou doménu)
- [ ] nastavit `config.local.php` s ostrými SMTP údaji
- [ ] ověřit SPF/DMARC pro odesílací adresu, jinak poptávky spadnou do spamu
- [ ] přesměrovat `www` → bez `www` (nebo naopak, podle původního webu)
