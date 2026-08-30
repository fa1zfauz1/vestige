---
name: family-archive-dev
description: Use for any work in the Family Digital Archive project (Nuxt 4 frontend, Laravel backend, Docker Compose, shadcn-vue UI). Covers node version pitfalls, the shadcn CLI, Docker frontend node_modules volume gotcha, TypeScript pinning, and build/typecheck verification steps.
---

# Family Digital Archive Development

Stack: Nuxt 4 SPA (`apps/frontend`, ssr:false) + Laravel backend (`apps/backend`) + PostgreSQL + MinIO + Redis, orchestrated by Docker Compose and the root `Makefile`. UI uses shadcn-vue (reka-ui) components.

## Critical environment facts

- **Shell Node is v18** (`~/.nvm/versions/node/v18.20.0`) but the tooling needs Node 20+.
  Prefix every local npm/npx/shadcn run with:
  `export PATH="$HOME/.nvm/versions/node/v24.19.0/bin:$PATH"`
- The **Docker frontend** container runs as **root** and bind-mounts `apps/frontend:/app`. It writes
  root-owned `.nuxt` / `.output` on the host — do NOT try to `rm -rf`/chown them; the container manages them.
  Host `.output`/`.nuxt` from prior Docker runs may be root-owned, causing local `rm -rf` failures. Move aside instead:
  `mv .output ._output_rootbak` (dir itself is writable via the parent even if children are root-owned).
- Frontend port 3000 is **not** exposed to the host; access via nginx on **port 80**.
- The Docker frontend `node_modules` must be installed **inside the container** — the compose has a
  node_modules volume (see below), so host `npm install` does not help the container.

## Docker frontend node_modules gotcha (important)

`docker-compose.yml` frontend service used an **anonymous** volume `- /app/node_modules`.
Every container recreation wiped it → Nuxt crashed in a loop with
"Could not load @nuxtjs/color-mode. Is it installed?" → nginx 502.

Fix (already applied, keep it): use a **named** volume:
```yaml
volumes:
  - ./apps/frontend:/app
  - frontend-node-modules:/app/node_modules
```
plus `frontend-node-modules:` in the top-level `volumes:` block. After recreating, populate via:
```bash
docker compose stop frontend
docker compose run --rm --no-deps frontend npm install
docker compose up -d frontend
```

## Tooling / dependencies

- Use **`shadcn-vue`** CLI (the unified `shadcn` CLI is React-only and has **no Nuxt template**).
  `npx --yes shadcn-vue@latest init -t nuxt --base reka --style nova --icon-library lucide --font inter -b neutral -y -c apps/frontend`
  (init hangs under Node 18; must run with Node 24 path above).
- **Pin TypeScript to ^5** — TypeScript 7 breaks `@vue/compiler-sfc` type resolution
  ("No fs option provided to compileScript"). `npm install -D typescript@^5.6`.
- `nuxi typecheck` needs `vue-tsc` and `@types/node`: `npm i -D vue-tsc @types/node@^20`.
- Current deps: `@nuxtjs/color-mode` (light/dark via `useColorMode`, class suffix `''`, default dark),
  `@lucide/vue` (NOT `lucide-vue-next` — icons are `FooIcon` names, verify with
  `node -e "console.log(typeof require('@lucide/vue').MenuIcon)"`).

## Verification workflow (local env may be root-tainted)

1. Syntax/type check inside the container (owns `.nuxt`): `docker compose exec frontend npx nuxi typecheck`
2. Health: `curl -s -o /dev/null -w "%{http_code}" http://localhost/` → expect 200;
   `/api/auth/google/redirect` → 302.
3. Local `npm run build` works but hits `EACCES rmdir .output/public` if `.output` is root-owned —
   move it aside first (`mv .output ._output_rootbak`).
4. Container logs: `docker compose logs frontend --tail 30 | sed 's/\x1b\[[0-9;?]*[a-zA-Z]//g'`

## UI conventions (shadcn-vue in Nuxt 4)

- Shadcn components live in `app/components/ui/<name>/` with `index.ts` re-exports; **import explicitly**:
  `import { Button } from '@/components/ui/button'` (`@` → `app/`).
- Nuxt auto-import collides with `index.ts` re-exports ("Two component files resolving to the same name").
  Already configured in `nuxt.config.ts`:
  `components: [{ path: '~/components', extensions: ['vue'] }]`.
- For **custom** `app/components/*.vue` (PhotoCard, PhotoDetailDialog, StatusBadge, ThemeToggle), import
  with the explicit `.vue` extension: `import PhotoCard from '@/components/PhotoCard.vue'`
  (extensionless `.vue` imports fail vue-tsc module resolution).
- Use `const route = useRoute()` when referencing `route` in layouts/pages.
- Dark theme default via `colorMode: { classSuffix: '', preference: 'dark', fallback: 'dark' }`.
  Add `color-scheme: light/dark` and theme transition to `app/assets/css/main.css`.
- Main layout: `app/layouts/default.vue` (sidebar + topbar + mobile drawer). App shell: `app/app.vue`
  (mounts `<Toaster />` from `@/components/ui/sonner`, exports `Toaster`).