# Spuštění na ostrých doménách

> **Spuštěno 7. 8. 2026.** Oba weby běží na ostrých doménách, certifikáty
> i přesměrování jsou nastavené. Níž zůstává postup a hlavně to, co se muselo
> udělat ručně mimo vps-centrum — viz „Ruční zásahy do nginxu".

Postup pro přehození `chata-prasilka.cz` a `truhlarstvi-suchanek.cz` na tenhle
server. Weby už tu běží na testovacích subdoménách — nic se nikam nestěhuje,
jen se přidají ostré domény a přehodí DNS.

## Fakta, na kterých postup stojí

| | |
|---|---|
| IP tohoto serveru | **37.235.102.28** |
| Kanonická adresa chaty | `https://www.chata-prasilka.cz/` (bez www → 301 na www) |
| Kanonická adresa truhlářství | `https://www.truhlarstvi-suchanek.cz/` (bez www → 301 na www) |
| Pošta chaty | Seznam Email Profi, na A záznamu webu nezávislá |
| Pošta truhlářství | `mail.gransy.com` přes CNAME, na A záznamu webu nezávislá |

**Sahá se jen na A záznam. MX a `www` CNAME zůstávají.** Ověřeno, že pošta
u obou domén běží jinde než web, takže ji přehození webu neohrozí.

## Pozor: chata posílá HSTS

`strict-transport-security: max-age=63072000; includeSubDomains; preload`

Každý prohlížeč, který na webu byl, si dva roky pamatuje, že tam smí jen po
HTTPS — a chybu certifikátu **nejde odkliknout**. Certifikát pro chatu proto
musí existovat **dřív, než se přehodí DNS**, ne po tom. Truhlářství HSTS
neposílá, ale postup je stejný pro oba.

## Než se sáhne na DNS

Tohle jde udělat kdykoliv předem, běžící weby to nijak neovlivní.

### 1. Domény ve vps-centeru

Založit `chata-prasilka.cz` i `truhlarstvi-suchanek.cz` včetně `www` a nastavit
přesměrování na kanonický tvar podle tabulky výš. Do nginxu ručně nesahat —
vps-center konfiguraci při aktualizaci přepíše.

### 2. Certifikáty přes DNS ověření

Webové ověření zatím projít nemůže, doména sem ještě nemíří:

```
certbot certonly --manual --preferred-challenges dns \
    --cert-name chata-prasilka.cz \
    -d chata-prasilka.cz -d www.chata-prasilka.cz

certbot certonly --manual --preferred-challenges dns \
    --cert-name truhlarstvi-suchanek.cz \
    -d truhlarstvi-suchanek.cz -d www.truhlarstvi-suchanek.cz
```

Certbot vypíše hodnotu pro `_acme-challenge` TXT — přidat do zóny a počkat, až
se rozšíří, teprve pak potvrdit.

**Takhle vystavený certifikát se sám neobnovuje.** Je jen na překlenutí
přepnutí; po něm se vystaví znovu přes web (krok 6) a obnovování se rozjede.

### 3. Nasazení do ostrého docrootu

```
/root/projects/janacek/deploy.sh chata-prasilka       ostry
/root/projects/janacek/deploy.sh truhlarstvi-suchanek ostry
```

Skript si docroot najde sám; kdyby ho vps-center pojmenoval nečekaně, dá se
přebít proměnnou `DOCROOT_OSTRY`. Testovací subdomény byly po spuštění
zrušené, `ostry` je proto výchozí cíl.

`robots.txt` dopisuje deploy podle cíle: test zakazuje indexaci, ostrý povoluje.
V gitu není právě proto, aby se to nemohlo splést.

### 4. Přihlašovací údaje k SMTP

Do každého ostrého docrootu zkopírovat `config.local.php` z testovacího —
deploy ho z rsyncu vylučuje, takže se sám nepřenese. Bez něj formulář
neodesílá. Skript na to upozorní.

### 5. Zkouška ještě před přepnutím DNS

Ostrý vhost jde vyzkoušet, aniž by se na DNS sáhlo — stačí adresu podstrčit:

```
curl -sI --resolve www.chata-prasilka.cz:443:37.235.102.28 \
     https://www.chata-prasilka.cz/
curl -sI --resolve www.truhlarstvi-suchanek.cz:443:37.235.102.28 \
     https://www.truhlarstvi-suchanek.cz/
```

Musí odpovědět 200 s platným certifikátem. Když tohle projde, přepnutí DNS už
nemá co pokazit.

## Přepnutí

TTL je u obou domén 600 s, takže se nemusí nic snižovat předem.

### 6. Přehodit A záznam

Na **37.235.102.28**. `www` CNAME a MX nechat být.

Do deseti minut se to rozšíří. Pak hned vystavit certifikáty znovu, tentokrát
přes web, aby se obnovovaly samy:

```
certbot certonly --webroot -w <docroot> --cert-name chata-prasilka.cz \
    -d chata-prasilka.cz -d www.chata-prasilka.cz --force-renewal
```

### 7. Ověřit

- [ ] HTTPS na obou doménách, platný certifikát
- [ ] Přesměrování: obojí bez www → www
- [ ] `robots.txt` povoluje indexaci
- [ ] Formulář odešle a poptávka dorazí
- [ ] Cookie lišta naskočí a dá se odmítnout
- [ ] Měření běží — v Analytics se do pár minut objeví návštěva. Rozjede se
      samo, je navázané na ostrou doménu.

## Po přepnutí

Staré weby (Solidpixels u chaty, WordPress u truhlářství) **nechat běžet aspoň
týden**. Kdyby se cokoliv pokazilo, návrat je otočení A záznamu zpět. Teprve
potom má smysl je rušit.

Nepovinné: dnešní web truhlářství má zásady na
`/wp-content/uploads/2026/01/zasady-ochrany-osobnich-udaju.pdf`. Kdyby na tu
adresu někdo odkazoval, dá se tam dát přesměrování na `/dokumenty/`. Oba weby
jsou jinak jednostránkové, takže se jiné adresy ztratit nemají.

## Ruční zásahy do nginxu

Nové vps-centrum tyhle věci nenabízí, takže jsou dopsané přímo do
`/etc/nginx/sites-available/domains_conf/<doména>.conf`. **Panel je při
regeneraci konfigurace může přepsat** — kdyby po zásahu ve vps-centeru přestalo
fungovat HTTPS nebo přesměrování, hledat nejdřív tady. Zálohy původních
souborů jsou v `/root/projects/janacek/zaloha-nginx-*.conf`.

Doplněno:

1. **Cesta k certifikátu** — panel nechával `ssl-bundle.pem`, tedy obecný
   serverový certifikát. Weby kvůli tomu hlásily chybu; u chaty se kvůli HSTS
   nedala odkliknout. Nastaveno na `/etc/letsencrypt/live/<doména>/`.

2. **Kanonická adresa** — všechno na `https://www.<doména>/`. Apex se
   přesměrovává rovnou na cílovou adresu, aby to byl jeden skok:

   ```
   if ($host = <doména>) { return 301 https://www.<doména>$request_uri; }
   if ($scheme != https)  { return 308 https://$host$request_uri; }
   ```

3. **HSTS na obou webech** — `max-age=63072000; includeSubDomains`, tedy dva
   roky. U chaty ho posílal i původní web; u truhlářství je to novinka na přání
   klienta. Bez `preload`, ten by znamenal zápis do seznamu v prohlížečích
   a nešel by snadno vzít zpět.

   Praktický dopad: **žádná subdoména těchto domén nesmí běžet bez HTTPS.**
   Pošty se to netýká, HSTS platí jen pro prohlížeče.

Certifikáty jsou vystavené přes webové ověření (`--webroot`), takže se
obnovují samy. Sdílený webroot je `/www/hosting/vas-server.cz/acme`.
