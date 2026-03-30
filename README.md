# Laravel Module Generator

Opinionated scaffolding for Laravel: model, migration, repository (+ interface), Spatie-style DTO stub, optional DAO layer, and HTTP controllers. Skips existing files unless you pass `--force`.

**Published on Packagist:** [`mqondisi/laravel-module-generator`](https://packagist.org/packages/mqondisi/laravel-module-generator)

## Requirements

- PHP `^8.1`
- Laravel **9.x through 13.x** (via `illuminate/*` `^9`–`^13`)

## Installation

```bash
composer require mqondisi/laravel-module-generator
```

The service provider is **auto-discovered**; you do not need to register it manually in most apps.

For bleeding-edge changes from GitHub:

```bash
composer require mqondisi/laravel-module-generator:dev-master
```

## Optional: Spatie Laravel Data

Generated DTOs under `app/Data` extend `Spatie\LaravelData\Data`. The package **suggests** installing Spatie in your application (it is not a hard dependency of this package, to avoid unnecessary transitive conflicts):

```bash
composer require spatie/laravel-data:^4
```

## Usage

```bash
php artisan make:module Customer --api
php artisan make:module Customer --inertia
php artisan make:module Customer --tenant
php artisan make:module Customer --with-dao
php artisan make:module Customer --api --tenant --with-dao
```

Flags can be combined. With both `--api` and `--inertia`, an API controller and a web Inertia controller are generated (plus a page stub when Inertia is used).

| Option | Effect |
|--------|--------|
| `--api` | JSON API controller under `app/Http/Controllers/Api` |
| `--inertia` | Web controller + page stub under `resources/js/Pages/.../` (`Index.vue`, `Index.jsx`, or `Index.svelte`) |
| `--inertia-stack` | With `--inertia`: `vue` (default), `react`, or `svelte` — picks stub file and extension |
| `--tenant` | `team_id` on migration, model global scope / `scopeTeam`, tenant docblocks |
| `--with-dao` | `app/DAO` + `app/DAO/Interfaces`; repository depends on the DAO only |
| `--force` | Overwrite files that already exist (use with care) |

Examples:

```bash
php artisan make:module Customer --inertia --inertia-stack=react
php artisan make:module Customer --inertia --inertia-stack=svelte
```

After a run, the command prints **Created**, **Skipped**, and **Overwritten** lines plus a short summary.

## Repository bindings

The generator creates or updates `app/Providers/RepositoryServiceProvider.php` and may append your provider to `config/app.php` when that file uses the usual `AppServiceProvider::class` marker (Laravel 9–10 style). On Laravel 11+, register `App\Providers\RepositoryServiceProvider::class` in `bootstrap/providers.php` if it is not picked up automatically.

## License

MIT
