# truhlarstvi-suchanek

Statický web. Větev `truhlarstvi-suchanek` repozitáře `csbotcz-oss/janacek`.

| | |
|---|---|
| Testovací | https://truhlarstvi-suchanek.cstest.cz/ |
| Ostrý (původní) | https://truhlarstvi-suchanek.cz/ |
| Docroot | `/www/hosting/cstest.cz/truhlarstvi-suchanek` |
| Worktree | `/root/projects/janacek/truhlarstvi-suchanek` |

Původní web běží na WordPressu 6.9 + Elementoru 4.0.2. Přepisuje se na statické
HTML/CSS — obsah bude spravovat csbot.cz, klient administraci nepotřebuje.

Referenční snapshot: `/root/backup/janacek/2026-07-30/truhlarstvi-suchanek/`

## Deploy

```sh
/root/projects/janacek/deploy.sh truhlarstvi-suchanek --dry-run   # náhled změn
/root/projects/janacek/deploy.sh truhlarstvi-suchanek             # nasazení
```

Skript stáhne větev z originu a zrcadlí ji rsyncem do docrootu (`--delete`).
Obsah docrootu se tedy vždy rovná obsahu větve — ruční úpravy v docrootu zmizí.
