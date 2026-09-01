---
name: family-archive-dev
description: Use for any work in the Family Digital Archive ("VESTIGE") project — Nuxt 4 SPA frontend, Laravel 13 backend, Docker Compose, shadcn-vue UI. Covers the environment gotchas (Node version, container node_modules volume, root-owned build dirs, Redis "null" password), routing/layout conventions, backend auth/audit patterns, and the verify + git push workflow.
---

# Family Digital Archive (VESTIGE) Development

Stack: Nuxt 4 SPA (`apps/frontend`, `ssr:false`) + Laravel 13 backend (`apps/backend`, PHP 8.4) +
PostgreSQL 16 + MinIO + Redis 7 + nginx, via Docker Compose and the root `Makefile`. UI is
shadcn-vue (reka-ui). **Roadmap Phase 1 (Foundation MVP) is complete**; Phases 2–7 (face tagging,
recognition, OCR, timeline, family tree, advanced AI) are untouched.

## Critical environment facts

- **Shell Node is v18**; tooling needs Node 20+. Prefix local npm/npx/shadcn runs with:
  `export PATH="$HOME/.nvm/versions/node/v24.19.0/bin:$PATH"`
- The **Docker frontend** binds `apps/frontend:/app` and runs as **root**, so `.nuxt`/`.output`
  become root-owned on the host. Never `rm -rf`/chown them — the container manages them. If a local
  build hit `EACCES rmdir .output`, move the dir aside: `mv .output ._output_rootbak`.
- **Frontend port 3000 is NOT exposed to the host** — any new feature is tested via nginx at
  **`http://localhost`** (port 80). `/map`, `/admin/users`, `/admin/activity`, etc. all proxy there.
- **frontend `node_modules` lives in a named Docker volume** (`frontend-node-modules`), NOT the host.
  Host `npm install` is useless for the running app; instead:
  `docker compose exec frontend npm install <pkg>` (or `docker compose run --rm --no-deps frontend npm install`).
  New shadcn-vue components write files via the bind mount (fine), but new top-level deps need a
  container install.
- **Redis "null" password footgun**: Laravel's `env()` converts the string `"null"` → PHP `null`
  (no auth), while the compose redis container starts with `--requirepass ${REDIS_PASSWORD}`.
  Use a real password (local dev: `devredispassword`) in `.env`/`apps/backend/.env`. Note Laravel's
  immutable Dotenv means the container **OS env wins over the .env file**: after changing env you
  must RECREATE the container (`docker compose up -d backend`), not just `restart`. This surfaced as
  `NOAUTH Authentication required.` (rate limiter uses redis cache).
- Backend runs `php -S` (no rebuild) — PHP edits apply per request. `config:clear` if env/config
  seems stale.

## Auth & access model (backend)

- Routes in `routes/api.php`:
  - `web` + `throttle:auth` → Google OAuth.
  - `auth:sanctum` + `throttle:api` → `user`, `appeal`, `logout`, `profile`, `profile/remove-avatar`.
  - `auth:sanctum` + `approved` + `throttle:api` → photos, approved-users, share, admin.
- `EnsureUserApproved` middleware (alias `approved` via `bootstrap/app.php`) → **403** for any
  non-`approved` user on content APIs; `/api/user` stays open so pending/denied users see their status.
- Rate limiters: `api` = 150/min per user-or-ip, `auth` = 15/min per ip (`AppServiceProvider`).
- Photo access: list filtered to owner-or-shared; `show` gated (`ensureAccess`); edit/rotate/delete
  owner-only (`ensureUploader`). Sharing via `POST /photos/{id}/share` with `user_ids[]`.
- **Don't call `$this->middleware()` in controllers** — removed from Laravel; apply middleware via
  route groups instead. `Socialite::driver('google')->stateless()` is fine (LSP flags it falsely).
- Google signups have `password = null` (users.password is nullable).

## Audit logging

- `activity_logs`: `actor_id`, `action` (`user.created|updated|deleted|approved|rejected|suspended|
  role_changed|appealed`, `login`, `logout`, `photo.created|updated|deleted|rotated|shared`),
  `description`, polymorphic `subject_type/subject_id` (User or Photo), `ip_address`, `user_agent`.
- Use `App\Support\AuditLog::record($action, $subject, $description, $actor)`; actor defaults to
  `auth('sanctum')->user()`. Wire into auth/profile/admin/photo controllers.
- Admin view: `GET /api/admin/activity-logs` (`?action=&from=&to=`), page `app/pages/admin/activity.vue`.

## MinIO URLs

- Bucket `photos` is **public-read**, so avatars use permanent public URLs (`{MINIO_PUBLIC_URL}/{bucket}/{path}`),
  appended via `User::getAvatarUrlAttribute()`. Photos keep **5-min signed URLs** signed against the
  **public** endpoint (see `PhotoController::signedObjectUrl`) — never string-replace the host of a
  presigned URL (breaks the SigV4 host signature → 403).

## Frontend conventions (shadcn-vue / Nuxt 4)

- Shadcn components in `app/components/ui/<name>/` (index.ts re-exports). **Import explicitly**:
  `import { Button } from '@/components/ui/button'` (`@` → `app/`). Nuxt is configured to only auto-register
  `.vue` components (`components: [{ path: '~/components', extensions: ['vue'] }]`) to avoid index.ts collisions.
- For custom `app/components/*.vue` import with the explicit `.vue` extension, e.g.
  `import PhotoDetailDialog from '@/components/PhotoDetailDialog.vue'` (vue-tsc needs it).
- **Routing under a page file**: `pages/admin.vue` + `pages/admin/users.vue` makes `admin.vue` a
  *parent* for `/admin/users` (nested route) → it renders the parent content. Fix: use
  `pages/admin/index.vue` for `/admin` and siblings (`users.vue`, `activity.vue`) for the rest.
- **Don't rely on the shadcn `Tabs` component for true horizontal tabs** — it rendered vertically in
  this setup. Build a plain segmented control (`grid grid-cols-N rounded-xl bg-muted p-1` + buttons).
- **reka `Select` quirks**: `SelectItem` values must be non-empty strings (an empty-string option
  throws "must have a value prop that is not an empty string" → 500/no dropdown). Use a sentinel
  value (`'all'`) instead. SelectContent is `z-[1300]` so dropdowns appear above dialogs.
- **z-index tiers**: photo dialog overlay/content `z-[1100]`, AlertDialog `z-[1200]`, SelectContent
  `z-[1300]` — needed because Leaflet panes sit at up to 1000. Keep dialogs above maps.
- Dark map = keep OSM tiles + CSS filter on `.dark .leaflet-tile` (no CARTO — it added an
  "API KEY REQUIRED" watermark).
- Buttons inside the pan/zoom image stage need `@pointerdown.stop` (stage pointer-capture retargets
  clicks). Pan only when zoomed; elastic spring-back clamps translate to image bounds.
- Active sidebar item = simple `route.path === item.to` (each route highlights only itself; Home is
  NOT highlighted on Gallery). Icon truth-table: verify any new icon with
  `node -e "console.log(typeof require('@lucide/vue').FooIcon)"` (package is `@lucide/vue`, NOT lucide-vue-next).

## High-level pages/routes

`/` home (pending/denied branches + appeal) · `/gallery` (masonry + detail dialog) · `/map`
(Leaflet, click marker opens photo) · `/upload` · `/profile` · `/admin` (Pending/Approved/Denied)
· `/admin/users` (manage: approve/suspend/promote-admin/delete) · `/admin/activity` (audit log).

## Verification

1. Migration: `docker compose exec backend php artisan migrate [--force]`
2. Typecheck (in container — it owns `.nuxt`): `docker compose exec frontend npx nuxi typecheck`
3. Compile/logs: `docker compose logs frontend --tail 30 | sed 's/\x1b\[[0-9;?]*[a-zA-Z]//g'`
4. Health: `curl -s -o /dev/null -w "%{http_code}" http://localhost/` (expect 200)
5. Live API tests: create a throwaway token via tinker, curl the endpoint, then clean up
   (`$u->tokens()->delete(); $u->delete();`).

## Git workflow

- Not a git mismatch trap: this repo pushes via **SSH** (`git@github.com:fa1zfauz1/vestige.git`).
- One feature per branch (`feat/…`, `fix/…`), user merges the PR on GitHub, then:
  `git fetch && git checkout main && git pull origin main`.
- Get explicit consent before pushing. `.env*` secrets are gitignored; `.env.example` must stay
  placeholder-free.