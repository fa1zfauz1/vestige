# VESTIGE — Family Digital Archive

> **For the stories that outlive us.**

A private, self-hosted family digital archive. Digitize physical photographs, preserve the
handwritten backs, capture dates and places, and keep the whole collection searchable — all
behind a family-only login.

Upload old photographs, preserve handwritten backs, capture dates and places, and keep access
limited to approved family members.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Quick Start (Dev)](#quick-start-dev)
- [Configuration](#configuration)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Production / Deployment](#production--deployment)
- [API Reference](#api-reference)
- [Roadmap](#roadmap)
- [Security Notes](#security-notes)

---

## Features

Implemented (Phase 1 — Foundation MVP):

- **Google OAuth login** (Laravel Socialite, stateless). First login creates the user account.
- **Admin approval workflow** — new members start as `pending`; an admin must `approve`,
  `reject`, or `suspend` them. Self-hosted roles: `admin` and `family_member`.
- **Photo uploads** — front image (required) + back image (optional), stored in MinIO (S3-compatible).
  Supported formats: JPG, PNG, WEBP. Max size configurable (default 20 MB).
- **Photo metadata** — title, year, approximate date, location, description, uploader.
- **Gallery** — responsive **masonry grid** with skeleton loading, hover metadata, empty states.
- **3D flip viewer** — dialogs to inspect a photo and flip between front / handwritten back.
- **Light / dark mode** toggle with persisted preference (dark by default).
- **Signed URLs** — images served via short-lived (5 min) MinIO pre-signed URLs; authenticated access only.
- **Audit logging schema** — `activity_logs` table (login / upload / edit / delete).

Planned (see [Roadmap](#roadmap)): face detection/tagging, face recognition, OCR of handwritten
notes, timeline & storytelling, family tree, duplicate detection and smart search.

---

## Tech Stack

| Layer         | Technology                        | Version / Detail                                  |
| ------------- | --------------------------------- | ------------------------------------------------- |
| **Frontend**  | Nuxt                               | 4.4.x (Vue 3 SPA, `ssr: false`)                  |
|               | Vue                               | 3.5.x                                             |
|               | TypeScript                        | 5.9.x (pinned to ^5 — TS 7 breaks Vue SFC types) |
|               | Vite                              | 7.x                                               |
|               | Tailwind CSS                      | 3.4.x                                             |
|               | shadcn-vue (components)           | style `reka-nova`, built on **Reka UI** 2.10.x    |
|               | Pinia                             | 3.x                                               |
|               | `@nuxtjs/color-mode`              | 4.x (light/dark)                                  |
|               | Icons                             | `@lucide/vue` 1.31.x                              |
|               | Toasts                            | `vue-sonner` 2.x                                  |
|               | Utils                             | `@vueuse/core`, `class-variance-authority`, `clsx`, `tailwind-merge` |
| **Backend**   | Laravel (framework)               | 13.x (`laravel/framework ^13.8`)                  |
|               | PHP                               | 8.4 (Docker `php:8.4-cli-alpine`; `composer.json` requires ^8.3) |
|               | Laravel Sanctum                  | 4.x (token auth)                                  |
|               | Laravel Socialite                | 5.x (Google OAuth)                                |
|               | Flysystem AWS S3 v3               | 3.x (MinIO disk)                                  |
| **Database**  | PostgreSQL                        | 16-alpine                                         |
| **Cache & Queue** | Redis                            | 7-alpine                                          |
| **Object Storage** | MinIO / MinIO Client (`mc`)    | latest (S3 compatible, path-style)                |
| **Reverse Proxy** | Nginx                           | `nginx:alpine`                                    |
| **AI Service** (Phase 3, placeholder) | Python · FastAPI | 3.12-slim, `uvicorn --reload` on :8001 |
| **OCR Service** (Phase 4, placeholder) | Python               | 3.12-slim                                         |
| **Orchestration** | Docker Compose                 | services: `nginx`, `backend`, `frontend`, `postgres`, `redis`, `minio`, `ai-service`, `ocr-service` |

---

## Architecture

```
Browser
   │  (http://localhost, single entry via nginx)
   ▼
┌──────────┐        /api/* & /storage/*        ┌─────────────────────┐
│  nginx   │ ─────────────────────────────────▶ │ Laravel backend     │
│  :80     │                                    │  php -S :8000        │
└──────────┘        /* (everything else)        └──────────┬──────────┘
   │            ▶ ┌─────────────────────┐                    │
   └──────────────▶  Nuxt frontend      │   Sanctum tokens,   ├──▶ PostgreSQL (users, photos, activity_logs)
                    │  Vite dev :3000    │   signed MinIO URLs ├──▶ Redis (cache/queue)
                    └─────────────────────┘                    └──▶ MinIO (photo objects, :9000/:9001 console)
```

- **Nginx** (`docker/nginx/default.conf`) is the only exposed port. `/api/*` and `/storage/*` go to
  the backend; everything else goes to the frontend (with WebSocket upgrade for Nuxt HMR).
- The **backend** runs Laravel on the PHP built-in dev server for development
  (`docker/backend/start.sh`) — every PHP file change applies on the next request (no build step).
- The **frontend** runs Nuxt in dev mode with **Vite HMR** — `.vue`/`.ts`/`.css` edits reflect in
  the browser instantly without rebuilding.

---

## Project Structure

```
.
├── apps/
│   ├── frontend/          # Nuxt 4 SPA (app/, components/, pages/, stores/, assets/)
│   ├── backend/           # Laravel app (app/Http/Controllers, routes/api.php, database/, config/)
│   ├── ai-service/        # FastAPI placeholder (TODO Phase 3)
│   └── ocr-service/       # OCR placeholder (TODO Phase 4)
├── docker/
│   ├── backend/           # Dockerfile, php.ini, start.sh
│   ├── frontend/          # Dockerfile
│   └── nginx/             # default.conf (reverse proxy)
├── docker-compose.yml     # All services
├── Makefile               # Developer task runner (make up, migrate, test, …)
├── .env.example           # Env template (copy to .env)
├── family-digital-archive-roadmap.md   # Full product roadmap (7 phases)
└── inspo/                 # UI reference screenshots
```

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) with **Docker Compose v2** (`docker compose`).
- `make` (GNU Make) — convenient but optional; every command has a plain `docker compose` equivalent.
- A **Google Cloud OAuth client** (OAuth 2.0 Web application) with:
  - Authorized redirect URI: `http://localhost/api/auth/google/callback`
  - (`http://<your-host>/api/auth/google/callback` if you change `APP_URL`)

---

## Quick Start (Dev)

```bash
# 1. Clone
git clone <your-repo-url> && cd family-digital-archive

# 2. Create .env from the template, then edit it
cp .env.example .env
#   - Set GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI
#   - Set APP_ADMIN_EMAIL (the admin account's Google email)

# 3. First-time scaffolding / env prep
make init

# 4. Build & start all Phase 1 services (nginx, backend, frontend, postgres, redis, minio)
make up

# 5. Generate the Laravel APP_KEY
make key-generate

# 6. Install frontend dependencies into the container (node_modules lives in a Docker volume)
docker compose run --rm --no-deps frontend npm install

# 7. Run migrations (includes Sanctum tables)
make migrate

# 8. Add dependencies if needed + create the MinIO bucket
docker compose exec backend composer install
make setup-minio
```

> **Tip:** if step 6 is skipped, the frontend container crash-loops with
> `Could not load @nuxtjs/color-mode` and nginx returns a 502. The frontend dependencies live in a
> **named volume** (`frontend-node-modules`) so they survive container restarts, but they must be
> (re)installed after pulling new code or on a fresh machine.

Open **http://localhost** — the app defaults to dark mode; use the toggle in the top bar.

Sign in with Google:

- If your email equals `APP_ADMIN_EMAIL`, the account is auto-approved as **admin**.
- Any other Google account is created as **family_member** with status **pending** until an admin
  approves it in the **Admin** panel (`/admin`).

---

## Configuration

Copy `.env.example` → `.env`. Key variables:

| Variable                  | Purpose                                            | Default                          |
| ------------------------- | -------------------------------------------------- | -------------------------------- |
| `APP_NAME`                | Project display name                               | `Family Digital Archive`         |
| `APP_ENV` / `APP_DEBUG`   | Laravel environment / debug                        | `local` / `true`                 |
| `APP_KEY`                 | Laravel app key (`make key-generate`)              | —                                |
| `APP_URL`                 | Public base URL                                    | `http://localhost`               |
| `FRONTEND_URL`            | Frontend origin used when redirecting after login  | `http://localhost`               |
| `APP_PORT`                | Host port mapped to nginx                          | `80`                             |
| `POSTGRES_DB/_USER/_PASSWORD` | PostgreSQL credentials                          | `family_archive` / `archive_user` / `secret` |
| `REDIS_PASSWORD`          | Redis password (`null` = none)                     | `null`                           |
| `MINIO_*`                 | MinIO root/access keys, bucket, endpoint, public URL | `minioadmin` / bucket `photos` |
| `GOOGLE_CLIENT_ID`        | Google OAuth client id                             | —                                |
| `GOOGLE_CLIENT_SECRET`    | Google OAuth client secret                         | —                                |
| `GOOGLE_REDIRECT_URI`     | OAuth callback URL                                 | `http://localhost/api/auth/google/callback` |
| `APP_ADMIN_EMAIL`         | Email auto-provisioned as admin at first login     | —                                |
| `UPLOAD_MAX_SIZE`         | Max upload size in MB                              | `20`                             |

`MINIO_PUBLIC_URL` is what the browser uses when rendering signed URLs — keep it as
`http://localhost:9000` locally, and point it at the publicly reachable MinIO endpoint in
production (or route `/storage/` through nginx with a custom proxy).

---

## Development Workflow

Everything is **bind-mounted**, so code edits are live without rebuilding:

- **Frontend hot reload (HMR)** — edit files under `apps/frontend/`; changes appear in the browser
  instantly. `nuxt.config.ts` or new Nuxt modules trigger an automatic dev-server restart.
- **Backend** — PHP runs per-request (`php -S` in dev), so controller/model/route edits apply on the
  next request. For schema changes run `make migrate` (or `make fresh` to reset + reseed).

Common tasks:

```bash
make up            # start all services (detached)
make down          # stop services
make logs          # tail logs from all services
make ps            # show container status
make migrate       # run Laravel migrations (includes Sanctum)
make fresh         # drop all tables, re-migrate, re-seed
make seed          # run database seeder (creates the admin account)
make key-generate  # generate Laravel APP_KEY
make shell-backend / shell-frontend / shell-postgres   # exec into a container
make artisan c="route:list"   # run an artisan command
make npm c="install"          # run npm inside the frontend container
make setup-minio              # create the MinIO bucket
```

Installing a new dependency:

```bash
# Frontend (must install in the container so it lands in the node_modules volume):
docker compose exec frontend npm install <package>
# or update package.json, then:  docker compose run --rm --no-deps frontend npm install

# Backend:
docker compose exec backend composer require <package>
```

Typechecking / linting:

```bash
# Run inside the frontend container (it owns .nuxt):
docker compose exec frontend npx nuxi typecheck
docker compose exec frontend npx eslint .        # when lint is configured
```

---

## Testing

```bash
make test            # backend tests + frontend tests
make test-backend    # php artisan test
make test-frontend   # npm run test (when frontend test setup is added)
```

---

## Production / Deployment

The project is designed to be **fully self-hosted and portable** (single Docker Compose stack).

1. Set production values in `.env` (`APP_ENV=production`, `APP_DEBUG=false`, strong secrets,
   real domain + HTTPS termination).
2. Build production images:

   ```bash
   docker compose build
   docker compose up -d
   ```

   > **Note:** the frontend Dockerfile currently runs the **dev server** (`npm run dev`), which is
   > intended for local HMR. For a production build, switch the frontend command to
   > `npm run build && npm run preview` (or serve `apps/frontend/.output` statically) — see the
   > [Roadmap / Infrastructure](#roadmap) section for hardening items.

3. **HTTPS** — terminate TLS at a reverse proxy (e.g., Cloudflare, Caddy, or an Nginx with a cert),
   since the bundled nginx config is plain HTTP with security headers.
4. **Storage** — expose MinIO's public endpoint (`MINIO_PUBLIC_URL`) or proxy `/storage/` to serve
   signed URLs from outside Docker.
5. **Backups** — daily `pg_dump` of PostgreSQL and archive of the MinIO buckets; recommended
   retention: 30 daily / 12 monthly snapshots.

---

## API Reference

Base URL: `/api` (proxied by nginx to the backend).

### Auth

| Method | Endpoint                            | Access   | Description                          |
| ------ | ----------------------------------- | -------- | ------------------------------------ |
| GET    | `/api/auth/google/redirect`         | Public   | Redirect to Google OAuth             |
| GET    | `/api/auth/google/callback`         | Public   | OAuth callback; returns token in URL fragment (`#token=`) |
| GET    | `/api/user`                         | Auth     | Current user                         |
| POST   | `/api/logout`                       | Auth     | Revoke current token                 |

### Photos

| Method | Endpoint            | Access   | Description                                             |
| ------ | ------------------- | -------- | ------------------------------------------------------- |
| GET    | `/api/photos`       | Auth     | Paginate photos (20/page) + short-lived signed URLs     |
| POST   | `/api/photos`       | Auth     | Upload `front_image` (+ optional `back_image`) + metadata |
| GET    | `/api/photos/{id}`  | Auth     | Show a photo with signed URLs                           |
| PUT    | `/api/photos/{id}`  | Auth     | Update metadata                                         |
| DELETE | `/api/photos/{id}`  | Auth     | Delete photo (removes objects from MinIO)               |

`POST /api/photos` fields: `title` (required), `front_image` (file, required, jpg/jpeg/png/webp,
≤ `UPLOAD_MAX_SIZE` MB), `back_image` (file, optional), `description`, `taken_year`,
`taken_date`, `location`.

### Admin (role `admin`, status `approved`)

| Method | Endpoint                                    | Description                         |
| ------ | ------------------------------------------- | ----------------------------------- |
| GET    | `/api/admin/pending-users`                  | List pending members                |
| GET    | `/api/admin/users`                          | List all members                    |
| POST   | `/api/admin/users/{id}/approve`             | Approve a member                    |
| POST   | `/api/admin/users/{id}/reject`              | Reject a member                     |
| POST   | `/api/admin/users/{id}/suspend`             | Suspend a member                    |

Health check: `GET /health` (nginx) → `200 healthy`.

---

## Roadmap

Full detail in [`family-digital-archive-roadmap.md`](./family-digital-archive-roadmap.md).

1. **Foundation MVP** (implemented) — auth, approval, uploads, gallery, flip viewer, audit logs.
2. **People & Face Tagging** — person directory, face detection, tag faces → people, search.
3. **Face Recognition** — embeddings, auto-suggest identity, confidence thresholds.
4. **OCR & Preservation** — extract handwriting from photo backs into searchable text.
5. **Timeline & Storytelling** — timeline browsing, captions/stories, family comments.
6. **Family Tree** — parent/child/sibling/spouse relationships, interactive visualization.
7. **Advanced Intelligence** — perceptual-hash duplicate detection, natural-language search.

Infrastructure hardening on the roadmap: HTTPS everywhere, production frontend build, monitoring
(Uptime Kuma / Prometheus / Grafana), and automated backups.

---

## Security Notes

- The frontend/backend historically worked with an **anonymous** `node_modules` volume that was
  wiped on container recreation (causing a nginx **502** crash-loop). This is now a **named volume**
  (`frontend-node-modules`) — keep it that way.
- Auth gates the UI, but **API authorization/approval enforcement** (e.g., a `pending`/`suspended`
  user writing photos) should be hardened before public deployment.
- ⚠️ **Rotate OAuth credentials before publishing this repo.** `.env.example` has been checked in
  with a real-looking Google client id/secret and a personal admin email. Replace those with
  placeholders and rotate the Google credentials as this may be a credential leak if the repo is
  public. Use `.env` (gitignored) for your real values.

---

## License

Private project — not licensed for redistribution. Keep your archive private.