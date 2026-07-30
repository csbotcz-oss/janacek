# janacek

Weby klienta Janáček. Každý web má **vlastní orphan větev** — větve nemají
společnou historii a nikdy se nemergují. Tahle větev (`main`) obsahuje jen
tenhle rozcestník.

| Větev | Web | Testovací | Původní |
|---|---|---|---|
| [`chata-prasilka`](../../tree/chata-prasilka) | Chata Prášilka | https://chata-prasilka.cstest.cz/ | https://www.chata-prasilka.cz/ |
| [`truhlarstvi-suchanek`](../../tree/truhlarstvi-suchanek) | Truhlářství Suchánek | https://truhlarstvi-suchanek.cstest.cz/ | https://truhlarstvi-suchanek.cz/ |

Oba weby jsou statické HTML/CSS one-pagery. Obsah spravuje csbot.cz, klient
administraci nemá.

## Uspořádání na serveru

Server `dus11-cs01.vas-server.cz`, vše pod `root`.

```
/root/projects/janacek/
├── .bare/                     # bare repozitář (sdílený objektový sklad)
├── deploy.sh                  # deploy skript (mimo git — je to serverová infra)
├── _main/                     # worktree větve main
├── chata-prasilka/            # worktree větve chata-prasilka
└── truhlarstvi-suchanek/      # worktree větve truhlarstvi-suchanek

/www/hosting/cstest.cz/
├── chata-prasilka/            # docroot — zrcadlo větve chata-prasilka
└── truhlarstvi-suchanek/      # docroot — zrcadlo větve truhlarstvi-suchanek
```

Do docrootu se nasazuje **rsyncem z worktree**, ne klonem — `.git` se tak nikdy
nedostane pod webserver (jinak by šlo stáhnout `/.git/config` a s ním historii).

## Deploy

```sh
/root/projects/janacek/deploy.sh <vetev> --dry-run   # náhled změn
/root/projects/janacek/deploy.sh <vetev>             # nasazení
```

Skript stáhne větev z originu (`reset --hard origin/<vetev>`) a zrcadlí ji do
docrootu s `--delete`. Docroot se tedy vždy přesně rovná větvi — **ruční úpravy
přímo v docrootu deploy smaže.**

Z rsyncu jsou vyloučené `.env` a `config.local.php` — tam patří přístupové údaje
(SMTP), které v gitu nesmí být a deploy je musí nechat na místě.

## Přístup ke gitu

Deploy key `deploy-janacek@dus11-cs01.vas-server.cz` (ed25519, read+write).
Privátní klíč `/root/.ssh/id_ed25519_janacek`, SSH alias `github-janacek`:

```sh
git clone git@github-janacek:csbotcz-oss/janacek.git
```

## Zálohy

`/root/backup/janacek/2026-07-30/` — snapshot obou původních ostrých webů
(veřejné HTML/CSS/JS/obrázky) pořízený před začátkem prací, včetně `.tar.gz`
a `SHA256SUMS`. Slouží jako vizuální předloha.

Není to záloha zdrojáků ani databází — k těm nemáme přístup.
