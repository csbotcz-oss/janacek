# chata-prasilka

Statický web. Větev `chata-prasilka` repozitáře `csbotcz-oss/janacek`.

| | |
|---|---|
| Testovací | https://chata-prasilka.cstest.cz/ |
| Ostrý (původní) | https://www.chata-prasilka.cz/ |
| Docroot | `/www/hosting/cstest.cz/chata-prasilka` |
| Worktree | `/root/projects/janacek/chata-prasilka` |

Původní web běží na Solidpixels (SaaS stavitel webů) — zdrojové kódy neexistují,
web se píše od nuly podle vizuální předlohy.

Referenční snapshot: `/root/backup/janacek/2026-07-30/chata-prasilka/`

## Deploy

```sh
/root/projects/janacek/deploy.sh chata-prasilka --dry-run   # náhled změn
/root/projects/janacek/deploy.sh chata-prasilka             # nasazení
```

Skript stáhne větev z originu a zrcadlí ji rsyncem do docrootu (`--delete`).
Obsah docrootu se tedy vždy rovná obsahu větve — ruční úpravy v docrootu zmizí.
