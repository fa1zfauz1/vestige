# VESTIGE — agent quick-start

Family Digital Archive: **Nuxt 4 SPA** (`apps/frontend`, `ssr:false`) + **Laravel 13** (`apps/backend`,
PHP 8.4) + **PostgreSQL 16** + **MinIO** + **Redis 7** + **nginx**, orchestrated with Docker Compose
and the root `Makefile`. Only Roadmap **Phase 1** is implemented.

**Load the `family-archive-dev` skill (`.opencode/skills/family-archive-dev/SKILL.md`) before working
here — it is the source of truth for conventions and gotchas.** This file plus the skill cover setup
and operations; `README.md` is the public-facing pitch.

## Do not break these

- **MinIO images changed.** MinIO removed `minio/minio` + `minio/mc` from Docker Hub (both 404) and
  made `quay.io/minio/*` private; the upstream repo is archived and source-only. `docker-compose.yml`
  pins community mirrors: server `ghcr.io/coollabsio/minio:2025-10-15T17-29-55Z`, client
  `bitnamilegacy/minio-client:latest`. **Never revert to the old upstream images** — pulls fail.
  To update: bump the dated tag from `ghcr.io/coollabsio/minio`, then `docker compose up -d minio`.
  Fallback: build from the (archived) upstream repo's Dockerfile. Longer term: migrate to another
  S3-compatible store — the app only needs the S3 API with path-style endpoints and bucket `photos`.
- **`NUXT_PUBLIC_API_BASE`** (on the `frontend` service in `docker-compose.yml`) must be
  `http://localhost/api` for local Docker. A stale/foreign value makes the SPA call the wrong backend.
- **First-run order:** `docker compose up -d --build` → `backend composer install` →
  `backend artisan key:generate` → `frontend npm install` (named volume) → `backend artisan migrate`
  → `docker compose run --rm minio-setup`.
- **`bootstrap/cache/` + `storage/framework/{sessions,views,testing}` + `storage/logs/`** are kept
  in git via `.gitignore` placeholders (Laravel needs them writable). If `composer install` fails with
  `bootstrap/cache directory must be present and writable`, run
  `docker compose exec backend sh -c "mkdir -p bootstrap/cache storage/framework/{sessions,views,cache,data} storage/logs"`.
- **Container OS env wins over `.env`** (Laravel's immutable Dotenv): after editing `.env`, *recreate*
  the affected containers (`docker compose up -d <svc>`), don't just `restart`.
- **Frontend port 3000 is NOT host-exposed** — test through nginx at **`http://localhost`**.
- Frontend `node_modules` lives in the named volume `frontend-node-modules`; host `npm install` does
  nothing for the running app. Install deps inside the container.
- Local shell Node is v18 (tooling needs 20+); the Docker frontend runs Node 20.

## Verify before finishing

- Typecheck: `docker compose exec frontend npx nuxi typecheck`
- Backend tests: `docker compose exec backend php artisan test`
- Health: `curl -s -o /dev/null -w "%{http_code}" http://localhost/` → expect `200`

## Git workflow

- **Every time:** propose the commit message and get explicit approval before committing, and
  **never `git push` without explicit permission.** One feature per branch (`feat/…`, `fix/…`,
  `docs/…`); the user merges the PR on GitHub.
- Commit style: Conventional Commits, lowercase subject (`feat: …`, `fix: …`, `docs: …`), matching
  `git log`.
- Run git from inside WSL (`wsl -d Ubuntu -- bash -lc "cd <repo> && git …"`) so Windows/WSL-mount
  file-mode quirks don't dirty the tree.

## Note

A `GET /api/debug-env` route exists in `apps/backend/routes/api.php` that echoes env config
unauthenticated. Remove it before any public deployment.
