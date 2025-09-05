# dpb-master-permission-guard

Guard **database table operations** in Laravel by mapping them to **Spatie permissions**. The package:

- discovers Eloquent models that opt‑in via a trait and caches a **table ⇒ model FQCN** map
- extends the **MySQL connection** so common query builder methods are **authorized** before hitting the DB
- provides a Filament page to **inspect/manage** permissions (WIP)

> Target stack: **Laravel 12**, **PHP ≥ 8.2**, **Livewire 3+**, **Filament 3+**, **spatie/laravel-permission 6.x**.

---

## Why
Large apps with many packages often need a **centralized, predictable permission scheme** at the **table** level, not just at the controller/policy level. This package enforces a simple contract:

```
Permission key: dpb-mpg.{table}.{operation}
Operations: create | read | update | delete
Example: dpb-mpg.users.read
```

If a table is protected and the current user lacks the permission for the targeted operation, the query is **blocked** with a 403.

---

## Installation

```bash
composer require dpb/dpb-master-permission-guard:dev-staging
```

The service provider is auto‑discovered via `extra.laravel.providers`.

**Requirements**
- PHP: ^8.2
- Laravel: 12
- spatie/laravel-permission: ^6.21
- dpb/dpb-utils: dev-staging

> Only **MySQL** is guarded. Other drivers are not yet extended.

---

## Opt‑in on models
Add the trait to any model you want to guard:

```php
use Dpb\MasterPermissionGuard\Concerns\HasPermissionGuard;

class User extends Model
{
    use HasPermissionGuard;
}
```

Then run discovery to populate the cache and seed permission keys:

```bash
php artisan mpg:discover
```

By default the command:
- scans PSR‑4 roots for models using the trait
- writes the mapping to cache key `mpg:tables`
- derives CRUD permission keys per protected table
- optionally seeds Spatie permissions (guards: `web` by default)

You can scope the scan:

```bash
php artisan mpg:discover \
  --paths=packages/dpb \
  --packages=dpb/dpb-master-permission-guard \
  --dry    # prints only, no writes
```

> On boot, `Registry::seed()` loads `cache('mpg:tables')`. Re‑run discovery after you add/change protected models or deploy to a fresh environment.

---

## How enforcement works

### Connection + Builder
The provider **extends the MySQL driver** so `connection()->query()` returns a custom `GuardedQueryBuilder`. Overridden methods call authorization before delegating to the parent:

- `get()`, `first()`, `count()` → **read**
- `insert()` / `insertGetId()` → **create**
- `update()` → **update**
- `delete()` → **delete**

If unauthorized, a `MissingPermissionException` is thrown.

```php
// Internals (conceptual)
PermissionGuardService::authorize($table, 'read');
// builds key dpb-mpg.{table}.read and checks current user via Spatie
```

### Raw SQL
A helper (`SqlInspector`) uses `EXPLAIN <sql>` to infer the target table(s) and map to CRUD. There are known gaps for statements where EXPLAIN does not apply or is ambiguous.

**TODOs / Known limitations**
- DDL and non‑EXPLAINable statements: `TRUNCATE`, `CREATE|ALTER|DROP`, `RENAME`, `LOAD DATA`, `LOCK/UNLOCK`, `CALL`, `SET|SHOW|DO`, `OPTIMIZE|ANALYZE|REPAIR`
- `INSERT ... VALUES` shows *No tables used* in EXPLAIN → the target table must be parsed
- multi‑table `DELETE t1,t2 ... JOIN ...` and `UPDATE ... JOIN ...`
- ensure all internal EXPLAINs pass bindings and are clearly tagged

Until these are finished, prefer the **query builder** API over arbitrary `DB::statement()` when you need enforcement.

---

## Permission keys

Helper builds consistent keys:

```php
PermissionGuardService::buildPermissionKey('users', 'read');
// => dpb-mpg.users.read
```

Assign them to roles/users using Spatie:

```php
use Spatie\Permission\Models\Permission;

Permission::findOrCreate('dpb-mpg.users.read', 'web');
$role->givePermissionTo('dpb-mpg.users.read');
```

---

## Filament integration (WIP)
A simple plugin and page are included:

- `Dpb\MasterPermissionGuard\Filament\Plugins\DpbMpgPlugin`
- `Dpb\MasterPermissionGuard\Filament\Pages\PermissionManagerPage`

Register the plugin in your panel provider:

```php
use Dpb\MasterPermissionGuard\Filament\Plugins\DpbMpgPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            new DpbMpgPlugin(),
        ]);
}
```

The page lists permissions with basic filters. Labels live in `resources/lang/sk/translations.php`.

---

## Exceptions and UX
`MissingPermissionException` returns HTTP 403. If the request expects JSON, the response is a small payload:

```json
{"error":"forbidden","op":"read","table":"users"}
```

A Filament notification is also triggered. Error handling is intentionally minimal for now.

---

## Caching
- Key: `mpg:tables`
- Loaded on boot by the service provider: `Registry::seed(cache()->get('mpg:tables', []))`

Clear or rebuild when protected models change:

```bash
php artisan cache:forget mpg:tables
php artisan mpg:discover
```

---

## Limitations
- Only **MySQL** is guarded.
- **Raw SQL coverage** is partial (see TODOs). Prefer the query builder.
- Package assumes a standard Spatie setup and a logged‑in user via the default guard (configurable in the command).

---

## Development
- Namespace: `Dpb\MasterPermissionGuard`
- Autoload: PSR‑4 from `src/`
- Language files: `resources/lang/sk`
- Views: `resources/views`
- Cache bootstrap in provider

---

## Roadmap
- Full raw SQL mapping parity
- Per‑table options (custom operations, guards)
- Driver‑agnostic guard (PostgreSQL, SQL Server)
- Better Filament UI for bulk permission assignment

---

## License
`proprietary` (see `composer.json`).

