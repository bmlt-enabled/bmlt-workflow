# Local dev environment — quickstart

Spins up WordPress + MySQL in Docker, with Mockoon standing in for a BMLT Root Server,
so you can click around the plugin in a browser.

## TL;DR (single site)

```bash
# 1. one-time setup
docker network create testnet                      # once ever
echo "127.0.0.1 wordpress-php8-singlesite" | sudo tee -a /etc/hosts   # once ever
# open Mockoon, load mockoon/bmlt3x.json, make sure the "Bmlt3x" env is running (port 3001)

# 2. start the stack (run from the docker/ directory)
cd docker
export BMLT=host.docker.internal BMLT_PORT=3001    # mac: reach Mockoon from the container
docker compose up --detach

# 3. open it
#   http://wordpress-php8-singlesite/wp-admin        login: admin / admin
#   plugin settings: /wp-admin/admin.php?page=bmltwf-options

# 4. stop
docker compose down
```

First boot takes ~15–30s while WordPress self-installs. The plugin is auto-activated and
pre-pointed at the mock BMLT server (`http://host.docker.internal:3001/main_server/`).

## Run the tests

**Unit tests (PHPUnit)** — no Docker/Mockoon needed, just PHP + Composer:

```bash
composer update                                   # once / after dependency changes
./vendor/bin/phpunit                              # run everything
./vendor/bin/phpunit tests/phpunit/src/BMLT/IntegrationTest.php   # one file
./vendor/bin/phpunit --filter test_createMeeting_requires_timezone  # one test
```

**End-to-end tests (TestCafe)** — needs the stack + Mockoon running:

```bash
npm install -g testcafe                           # once

# bring the whole stack up the way the tests expect it:
cd docker && ./run_tests.sh                        # = docker compose up with BMLT=en0 IP
# on a Mac where the firewall blocks the LAN IP, start it this way instead:
#   cd docker && BMLT=host.docker.internal BMLT_PORT=3001 docker compose up --detach

# make sure the Mockoon "Bmlt3x" env is running, then from the repo root:
testcafe                                           # all e2e tests
# on a Mac, point the tests' restore URLs at the reachable host:
#   BMLTWF_BMLT_HOST=host.docker.internal testcafe
testcafe chrome tests/testcafe/geocoding_tests.js  # a single test file
```

## Why `host.docker.internal` and not the LAN IP

`docker/run_tests.sh` (used by CI) sets `BMLT=$(ipconfig getifaddr en0)` — the Mac's LAN IP.
The macOS firewall blocks the container from reaching that IP, so for local dev use
`BMLT=host.docker.internal` instead (Docker Desktop resolves it to the host). The same
applies to anything you type into the plugin's BMLT server / Geo Service URL fields:

| Who makes the request | Use |
|---|---|
| Plugin PHP (inside the container) — BMLT server URL, Geo Service URL | `http://host.docker.internal:3001` |
| Your browser / host shell (to eyeball the mock) | `http://localhost:3001` |

`host.docker.internal` does **not** resolve in your browser — only inside the container.

## Ports

| Service | URL | Hosts entry needed |
|---|---|---|
| Single site | http://wordpress-php8-singlesite (`:80`) | `127.0.0.1 wordpress-php8-singlesite` |
| Multisite (subsite) | http://wordpress-php8-multisitesingle:81/... | `127.0.0.1 wordpress-php8-multisitesingle` |
| Multisite network | http://wordpress-php8-multinetwork:82/... | `127.0.0.1 wordpress-php8-multinetwork` |
| DB-upgrade test site | http://wordpress-php8-dbupgrade:83 | `127.0.0.1 wordpress-php8-dbupgrade` |
| Mockoon (mock BMLT + geo service) | http://localhost:3001 | — |

All WordPress logins are `admin` / `admin`.

## Mockoon

- Environments live in `mockoon/` (`bmlt3x.json` is the main one, port 3001).
- It also serves the geo service routes (`/api/geocode`, `/api/timezone`) and a catch-all
  `/main_server/api/v1/meetings/:id` so any meeting resolves.
- **After editing a `mockoon/*.json` file, reload that environment in Mockoon** (close and
  re-open it) — Mockoon keeps its own in-memory copy and won't pick up external edits until
  you reload.

## Troubleshooting

- **`network testnet declared as external, but could not be found`** → `docker network create testnet`.
- **"Error establishing a database connection"** → the install scripts now create the DB
  with `mariadb --skip-ssl` (the MySQL 5.7 test DB runs with SSL off, the newer client
  requires it). If you still see it, `docker restart bmlt-workflow-wordpress-php8-singlesite-1`
  to re-run the installer once the DB is up.
- **Changes to the plugin not showing** → the plugin is copied into the container at install;
  `docker exec bmlt-workflow-wordpress-php8-singlesite-1 sync-plugin.sh` re-syncs it.
- **"Error retrieving meeting details" on the form** → you picked a meeting Mockoon doesn't
  mock; reload the Mockoon env to pick up the catch-all meeting route.
