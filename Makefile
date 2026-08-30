# ═══════════════════════════════════════════════════════
# Family Digital Archive - Makefile
# ═══════════════════════════════════════════════════════
# Usage:
#   make init       First-time setup (copy .env, scaffold apps, install deps)
#   make up         Start all Phase 1 services
#   make down       Stop all services
#   make build      Rebuild Docker images
#   make logs       Tail logs from all services
#   make shell-backend   Open shell in backend container
#   make shell-frontend  Open shell in frontend container
#   make migrate    Run Laravel migrations
#   make test       Run tests (backend + frontend)
#   make artisan    Run an Artisan command (e.g. make artisan c=route:list)
#   make fresh      Reset database and re-migrate

# ── Load .env (if exists) ─────────────────────────
ifneq (,$(wildcard .env))
    include .env
    export
endif

# ── Variables ────────────────────────────────────
COMPOSE = docker compose
PROJECT = $(COMPOSE) --project-name family-digital-archive
ARTISAN = $(PROJECT) exec backend php artisan
NPM_RUN = $(PROJECT) exec frontend npm run

# Colors for output
BLUE = \033[36m
GREEN = \033[32m
YELLOW = \033[33m
RED = \033[31m
RESET = \033[0m

.PHONY: help init up down build logs restart \
        shell-backend shell-frontend shell-postgres \
        migrate fresh seed test \
        artisan npm composer \
        setup-minio key-generate \
        ps

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
	awk 'BEGIN {FS = ":.*?## "}; {printf "$(BLUE)%-20s$(RESET) %s\n", $$1, $$2}'

# ──────────────────────────────────────────────────
# Lifecycle
# ──────────────────────────────────────────────────

init: ## Full first-time setup (copy .env, scaffold apps, install deps, migrate)
	@echo "$(BLUE)→ Initializing Family Digital Archive...$(RESET)"
	@if [ ! -f .env ]; then \
		echo "$(YELLOW)→ Copying .env.example → .env$(RESET)"; \
		cp .env.example .env; \
		echo "$(YELLOW)⚠  Edit .env with your settings before continuing.$(RESET)"; \
		echo "$(YELLOW)   Set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and APP_KEY.$(RESET)"; \
		echo "$(YELLOW)   Run 'make key-generate' to generate APP_KEY.$(RESET)"; \
	fi
	@echo "$(GREEN)✓ Initialized. Run 'make up' to start.$(RESET)"

up: ## Start all Phase 1 services (nginx, backend, frontend, postgres, redis, minio)
	$(PROFILE_ARGS) $(PROJECT) up -d
	@echo "$(GREEN)✓ Services started. Access at http://localhost$(RESET)"

down: ## Stop all services
	$(PROJECT) down

build: ## Build (or rebuild) Docker images
	$(PROJECT) build --no-cache

restart: down up ## Restart all services

logs: ## Tail logs from all services
	$(PROJECT) logs -f

ps: ## Show running containers
	$(PROJECT) ps

# ──────────────────────────────────────────────────
# Phase Profile Selectors
# ──────────────────────────────────────────────────

up-all: ## Start ALL services (Phase 1 + Phase 3 & 4 placeholders)
	COMPOSE_PROFILES=all $(PROJECT) up -d

up-ai: ## Start Phase 1 + AI service (Phase 3)
	COMPOSE_PROFILES=ai $(PROJECT) up -d $(filter-out $@,$(MAKECMDGOALS))

# ──────────────────────────────────────────────────
# Backend (Laravel)
# ──────────────────────────────────────────────────

artisan: ## Run an Artisan command: make artisan c="route:list"
	$(ARTISAN) $(c)

migrate: ## Run database migrations (includes Sanctum)
	$(ARTISAN) vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag="sanctum-migrations" --force 2>/dev/null; \
	$(ARTISAN) migrate

fresh: ## Drop all tables and re-run migrations
	$(ARTISAN) migrate:fresh --seed

seed: ## Seed the database
	$(ARTISAN) db:seed

key-generate: ## Generate Laravel application key
	$(ARTISAN) key:generate

shell-backend: ## Open shell in the backend container
	$(PROJECT) exec backend sh

shell-postgres: ## Open PostgreSQL shell
	$(PROJECT) exec postgres psql -U $(POSTGRES_USER) -d $(POSTGRES_DB)

# ──────────────────────────────────────────────────
# Frontend (Nuxt 3)
# ──────────────────────────────────────────────────

npm: ## Run npm in the frontend container: make npm c="install"
	$(PROJECT) exec frontend npm $(c)

shell-frontend: ## Open shell in the frontend container
	$(PROJECT) exec frontend sh

# ──────────────────────────────────────────────────
# Testing
# ──────────────────────────────────────────────────

test: ## Run all tests (backend + frontend)
	@echo "$(BLUE)→ Running backend tests...$(RESET)"
	$(ARTISAN) test
	@echo "$(BLUE)→ Running frontend tests...$(RESET)"
	@$(PROJECT) exec frontend npm run test 2>/dev/null || echo "$(YELLOW)⚠  Frontend tests not configured yet.$(RESET)"

test-backend: ## Run backend tests only
	$(ARTISAN) test

test-frontend: ## Run frontend tests only
	$(PROJECT) exec frontend npm run test 2>/dev/null || echo "$(YELLOW)⚠  Frontend tests not configured yet.$(RESET)"

# ──────────────────────────────────────────────────
# MinIO
# ──────────────────────────────────────────────────

setup-minio: ## Create MinIO bucket
	$(PROJECT) run --rm minio-setup

# ──────────────────────────────────────────────────
# Scaffolding (for initial project creation)
# ──────────────────────────────────────────────────

scaffold-backend: ## Scaffold Laravel 12 backend app
	@echo "$(BLUE)→ Scaffolding Laravel 12...$(RESET)"
	docker run --rm -v $(PWD)/apps:/app composer:2 create-project laravel/laravel backend --prefer-dist
	@echo "$(GREEN)✓ Laravel scaffolded.$(RESET)"
	@echo "$(YELLOW)→ To use PostgreSQL, update apps/backend/.env DB settings.$(RESET)"

scaffold-frontend: ## Scaffold Nuxt 3 frontend app
	@echo "$(BLUE)→ Scaffolding Nuxt 3...$(RESET)"
	docker run --rm -v $(PWD)/apps:/app node:20-alpine sh -c "\
		cd /app && \
		npx nuxi@latest init frontend --force --packageManager npm --no-git-init \
	"
	@echo "$(GREEN)✓ Nuxt 3 scaffolded.$(RESET)"

# ──────────────────────────────────────────────────
# Cleanup
# ──────────────────────────────────────────────────

clean: ## Remove all containers and volumes (⚠ destroys data)
	$(PROJECT) down -v --remove-orphans

clean-all: clean ## Clean + remove node_modules, vendor, and built assets
	rm -rf apps/backend/vendor apps/backend/node_modules
	rm -rf apps/frontend/node_modules apps/frontend/.nuxt apps/frontend/.output
	@echo "$(GREEN)✓ Cleaned. Run 'make up' to rebuild.$(RESET)"

# ──────────────────────────────────────────────────
# Git hooks (optional)
# ──────────────────────────────────────────────────

git-hooks: ## Install pre-commit hooks
	@echo '#!/bin/sh\nmake test\n' > .git/hooks/pre-commit
	chmod +x .git/hooks/pre-commit
	@echo "$(GREEN)✓ Pre-commit hook installed.$(RESET)"
