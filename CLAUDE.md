# CLAUDE.md — ClinGen Gene Tracker

## Project
ClinGen Gene Tracker (GT) — a Laravel 11 + Vue 2 application that tracks gene-disease curations for Gene Curation Expert Panels (GCEPs). Manages curation workflows, status tracking, and data exchange with external ClinGen systems.

## Tech Stack
- **Backend:** PHP 8.2, Laravel 11, MySQL 8.4, Redis
- **Frontend:** Vue 2.6, Vue Router 3, Vuex 3, Bootstrap Vue 2, Vite 7 (`@vitejs/plugin-vue2`)
- **Infrastructure:** Docker Compose — 7 services: db, app, nginx, redis, scheduler, queue, mailpit

## Build & Run
```bash
cp .env.example .env   # then edit: set DOCKER_USER, DB_PASSWORD, REDIS_PASSWORD, COMPOSER_AUTH
docker compose up -d   # starts all services; app at localhost:8012

# Frontend
npm install && npm run dev      # Vite dev server (HMR)
npm run build                   # Vite production build → public/build/

# Composer inside container
docker compose run --no-deps --rm -it --entrypoint composer app install
```

## Testing
```bash
php artisan test          # or ./vendor/bin/phpunit
```
- phpunit.xml: `DB_CONNECTION=testing` (SQLite), `CACHE_DRIVER=array`, `QUEUE_DRIVER=sync`
- Tests: `tests/Feature/`, `tests/Unit/`
- No frontend test suite

## Directory Layout

### Backend
| Path | Purpose |
|------|---------|
| `app/` (root) | Eloquent models (Curation, User, ExpertPanel, Gene, Disease, etc.) |
| `app/Http/Controllers/` | Web controllers; `Api/` subdirectory for API controllers |
| `app/Http/Requests/` | Form request validation |
| `app/Http/Resources/` | API resource transformers |
| `app/Policies/` | Authorization policies |
| `app/Actions/` | Action classes (laravel-actions pattern) |
| `app/Jobs/` | Queue jobs |
| `app/Services/` | Service classes |
| `app/Clients/` | External API clients (`OmimClient`, `Omim/OmimEntry`) |
| `app/DataExchange/` | Kafka integration |
| `app/Gci/`, `app/Hgnc/`, `app/Mondo/` | Domain-specific integrations (GCI, HGNC, Mondo) |
| `routes/web.php` | Web routes |
| `routes/api.php` | Internal API routes |
| `routes/api_external.php` | External API routes |
| `config/` | Laravel config files |
| `database/migrations/` | Database migrations |

### Frontend
| Path | Purpose |
|------|---------|
| `resources/assets/js/app.js` | SPA entry point |
| `resources/assets/js/routing.js` | Vue Router config (lazy-loaded route components) |
| `resources/assets/js/store/` | Vuex store modules |
| `resources/assets/js/repositories/` | API client layer |
| `resources/assets/js/components/` | Vue components (organized by feature) |
| `resources/assets/sass/` | SCSS stylesheets |
| `resources/views/layouts/app.blade.php` | Blade layout (injects user/config into `window` globals) |
| `public/build/` | Vite build output (manifest + hashed assets) |

## Architecture Patterns

### Auth
- **Web:** Session-based
- **Internal API:** Laravel Passport
- **External API:** Laravel Sanctum
- **Roles:** Spatie laravel-permission (`admin`, `programmer` roles + per-panel permissions)

### Admin
- Laravel Backpack at `/admin`

### Code Conventions
- Models use `$fillable` for mass assignment, `$casts` for attribute casting
- Models use `RevisionableTrait` for audit trails
- Jobs often use `dispatchSync()` for synchronous execution
- Frontend builds (`public/build/`) are committed to the repo
- API resources (`app/Http/Resources/`) transform models for JSON responses
- Form requests (`app/Http/Requests/`) handle validation
- Policies (`app/Policies/`) handle authorization

## Environment / Secrets
- Never commit `.env` — use `.env.example` as template
- Key secrets: `DB_PASSWORD`, `REDIS_PASSWORD`, `OMIM_API_KEY`, `COMPOSER_AUTH` (Backpack Pro), `AFFILIATIONS_API_KEY`
- `DOCKER_USER` must be set (UID:GID format)
