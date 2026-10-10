# VESTIGE — Family Digital Archive

> **For the stories that outlive us.**

A private, self-hosted home for your family's photographs.

Digitize the prints from the shoebox, keep the handwriting on the back, record where and when each
moment happened, and browse it all behind a family-only login. No cloud, no ads, no data leaving
your own infrastructure.

## Why

Family photos scatter across phones, drives and dusty boxes. VESTIGE gathers them into one place you
own — searchable, story-rich, and shared only with the relatives you approve.

## Highlights

- **Family-only access** — sign in with Google; an admin approves every new member.
- **Front & back scanning** — keep each photo together with its handwritten back, so no memory is lost.
- **Rich context** — title, year, approximate date, GPS-pinned place and a description on every photo.
- **3D flip viewer** — view full-screen, flip to the back, zoom and rotate.
- **Gallery & map** — a masonry gallery to browse, and a map of the places memories were made.
- **Private by design** — short-lived signed image URLs and an audit log of every action.

## Roadmap

1. **Foundation MVP** ✅ — auth, approvals, uploads, gallery, flip viewer, audit logs
2. People & face tagging
3. Face recognition
4. OCR of handwritten notes
5. Timeline & storytelling
6. Family tree
7. Advanced intelligence — duplicate detection & smart search

Full detail: [`family-digital-archive-roadmap.md`](./family-digital-archive-roadmap.md).

## Built with

Nuxt 4 (Vue 3 SPA) · Laravel 13 (PHP 8.4) · PostgreSQL 16 · MinIO · Redis 7 · nginx — orchestrated
with Docker Compose as one portable, self-hosted stack. Planned: FastAPI services for face
recognition and handwriting OCR.

## Run it

You'll need Docker (Compose v2) and a Google Cloud OAuth client whose redirect URI is
`http://localhost/api/auth/google/callback`.

```bash
cp .env.example .env                               # set GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / APP_ADMIN_EMAIL
docker compose up -d --build
docker compose exec backend composer install
docker compose exec backend php artisan key:generate
docker compose run --rm --no-deps frontend npm install
docker compose exec backend php artisan migrate
docker compose run --rm minio-setup
```

Open **http://localhost** and sign in with Google. The account matching `APP_ADMIN_EMAIL` becomes
the admin; everyone else starts as *pending* until approved.

> Setup order, environment gotchas and operational notes live in [`AGENTS.md`](./AGENTS.md).

## License

Private project — not licensed for redistribution. Keep your archive private.
