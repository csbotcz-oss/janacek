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

**Stav: hotovo na desktopu, tabletu i mobilu, ověřeno proti snímkům
ostrého webu.** Pásy i vlny sedí na 1440, 800 i 500 px do 2 px.

Zlomy jsou 992 a 719 px jako v originálu. Na tabletu se sloupce
neskládají — mřížka je stejná jako na desktopu, mění se jen velikosti
písma, odsazení a výšky mezipásů. Odkazy, které se nevejdou do lišty, se
přesouvají do rozbalovátka „Více"; hamburger nasazuje až pod 720 px.

Bloky najíždějí zdola (posun 48 px, 0,9 s, cubic-bezier(.215,.61,.355,1),
průhlednost o 0,1 s opožděná) — stejně jako originál přes plugin
inViewport. Výchozí skrytý stav zapíná skript v hlavičce, aby bez JS ani
s vypnutými animacemi obsah nezmizel.

Struktura: meruňková je barva celé stránky, bílé pásy leží na ní. Bílý pás
má vlnu ve svých prvních 50 px a v posledních 50 px tutéž vlnu otočenou
o 180° — proto vypadá každý bílý blok zrcadlově. Mezi bílou sekcí
a následujícím meruňkovým pásem je ještě 60px bílý pruh (`.mezipas`), který
nese spodní vlnu.

Svislý rytmus vychází z toho, že Solidpixels obaluje každý blok textu divem
s 14px vnitřním odsazením — mezi bloky je tedy 28 px, v ceníku a kontaktech
25,6 px. Tlačítka v obsahu jsou 54 px vysoká včetně 1px rámečku, tlačítko
v hlavičce 38 px, pole formuláře 42 px s černým rámečkem.

Vědomé odchylky:
- odstraněn odznak „Made by" (odkaz na netpromotion.cz)
- cookie lišta se nedělá, v patičce proto chybí „Nastavení cookies"
- u odkazu na adresu chybí ikonka „otevře se v novém okně"

## Odesílání formulářů

Běží přes mailserver klienta: `mail.cstech.cz`, **port 25 se STARTTLS**.
Port 465 nás z tohohle serveru odmítá (`554 Permission denied`) a 587 je
zavřený — spojení je i tak šifrované.

Poptávky chodí na `racajda.m@seznam.cz` (chata) a `truhlarstvi.hesu@seznam.cz`
(truhlářství).

**SPF pro `webmailer.cstech.cz` se vědomě nedoplňuje** — rozhodnutí klienta,
upozorněno na to bylo. Doména má MX, ale ne SPF, takže odesílání není ověřené
a u Seznamu, kam obě adresy vedou, může pošta padat do spamu. Kdyby si někdo
stěžoval, že poptávky nechodí, hledat problém nejdřív tady: stačí TXT záznam
`v=spf1 ip4:82.208.14.50 -all`.

Přihlašovací účty a hesla jsou v `config.local.php` v docrootu každého webu.
Nejsou v gitu a deploy je z rsyncu vylučuje, ověřeno. Jako odesílatel slouží
ten samý webmailerový účet; e-mail zákazníka jde do `Reply-To`, takže se dá
odpovídat rovnou.

## Co zbývá

1. **Google Analytics a cookie lišta.** Domluvené, ale nezačaté. GA se nesmí
   spustit před souhlasem, takže lišta musí být první. Klient chce navázat na
   stávající historii, takže se použije to samé měřicí ID, co běží na ostrých
   webech.
2. **Spuštění na ostrých doménách** — checklist je v README každé větve
   (robots.txt, canonical, SPF/DMARC, přesměrování www).

## Poznámky k postupu

Odhadovat hodnoty z CSS nefunguje. Elementor definuje proměnné, které
z valné části nepoužívá; Solidpixels přičítá k odsazení sekce vnitřní
okraje. Vždy měřit vykreslený výsledek — nástroje a jejich pasti jsou
popsané v `nastroje/README.md`.

U chaty navíc nestačí ani sonda: Solidpixels drží před scroll-animací
`transform: translateY(48px)` a v dumpu DOMu bývá u části sekcí ještě
neodstraněný. Sonda pak hlásí obsah o 48 px níž, než ve skutečnosti je.
Relativní geometrie uvnitř sekce je z ní správně, ale **počátek se musí
vzít ze snímku** — na to jsou `nastroje/radky.php` a `nastroje/prechody.php`.

Ve snímku ostrého webu zase ta samá animace část sekcí vůbec neodkryje.
Na to je `nastroje/snimek-chata.php`, který třídu `in-viewport` nasadí
ručně. Fotky pod ohybem se ani tak nenačtou — porovnávat jde jen text
a hranice pásů.
