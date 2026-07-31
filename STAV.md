# Stav prací — klient Janáček

Poslední aktualizace: 31. 7. 2026

Dva weby jednoho klienta, přepisované ze SaaS/WordPressu na statické HTML.
Cíl je **vizuální shoda 1:1 s ostrými weby** (dohodnuto s klientem).

## Kde co je

| | |
|---|---|
| Repozitář | `git@github-janacek:csbotcz-oss/janacek.git` (deploy key, read+write) |
| Bare repo | `/root/projects/janacek/.bare` |
| Worktrees | `_main/`, `truhlarstvi-suchanek/`, `chata-prasilka/` |
| Deploy | `/root/projects/janacek/deploy.sh <vetev> [--dry-run]` |
| Nástroje na měření | `/root/projects/janacek/nastroje/` (viz README tamtéž) |
| Zálohy a podklady | `/root/backup/janacek/2026-07-30/` |

Deploy zrcadlí větev do docrootu rsyncem s `--delete`; vyloučené jsou
`.env`, `config.local.php`, `*.md`, `*.vzor`.

## Truhlářství Suchánek

https://truhlarstvi-suchanek.cstest.cz/ — původně WordPress + Elementor.

**Stav: hotovo na desktopu, mobilu i tabletu.** Odladěno přes měření
vypočítaných stylů na 1440 / 768 / 375 px.

| šířka | typografických rozdílů | max svislý posun |
|---|---|---|
| 1440 | 1 | 63 px |
| 768 | 1 | 76 px |
| 375 | 1 | 50 px |

Vědomé odchylky od originálu (na přání klienta):
- mezera 20 px nad formulářem na tabletu a mobilu (originál ji nemá)
- tlačítko v CTA banneru vede na `#kontakt` (originál má mrtvé `#`)
- odstraněn odznak „made by"
- cookie lišta se nedělá

## Chata Prášilka

https://chata-prasilka.cstest.cz/ — původně Solidpixels, zdrojáky neexistují.

**Stav: desktop doladěný, mobil a tablet neprojeté.**

Výšky všech sekcí sedí přesně, pozice do 8 px, celá stránka −15 px z 8338.

Struktura: střídající se meruňkové a bílé pásy, na každém předělu zvlněná
hrana 50 px, plus 60px bílé mezipásy na čtyřech místech (mezi ubytováním
a galerií, vybavením a rezervací, aktivitami a ceníkem, kontaktem
a patičkou).

Vědomé odchylky:
- odstraněn odznak „Made by" (odkaz na netpromotion.cz)
- cookie lišta se nedělá

## Co zbývá

1. **SMTP na obou webech.** Formuláře jsou hotové včetně antispamu, ale
   chybí přístupové údaje. Bez nich vracejí srozumitelnou hlášku s kontaktem.
   Postup: `cp config.local.php.vzor <docroot>/config.local.php` a doplnit
   host, port, uživatele, heslo. Odesílací adresa musí patřit k doméně,
   jinak to spadne do spamu kvůli SPF.
2. **Chata: mobil a tablet.** Zatím vůbec neprojeté.
3. **Spuštění na ostrých doménách** — checklist je v README každé větve
   (robots.txt, canonical, SPF/DMARC, přesměrování www).

## Poznámky k postupu

Odhadovat hodnoty z CSS nefunguje. Elementor definuje proměnné, které
z valné části nepoužívá; Solidpixels přičítá k odsazení sekce vnitřní
okraje. Vždy měřit vykreslený výsledek — nástroje a jejich pasti jsou
popsané v `nastroje/README.md`.
