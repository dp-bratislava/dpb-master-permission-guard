# dpb-master-permission-guard

Guard **database table operations** in Laravel by mapping them to **Spatie permissions**. The package:

- discovers Eloquent models that opt‑in via a trait and caches a **table ⇒ model FQCN** map
- extends the **MySQL connection** so common query builder methods are **authorized** before hitting the DB
- provides a Filament page to **inspect/manage** permissions (WIP)
- provides traits for **Pages and Components** to automatically generate permissions based on FQCN and control access

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
use Dpb\MasterPermissionGuard\Concerns\HasTableGuard;

class User extends Model
{
    use HasTableGuard;
}
```

Then run discovery to populate the cache and seed permission keys:

```bash
php artisan mpg:discover
```

By default the command:
- scans PSR‑4 roots for models using the trait
- writes the mapping to cache key `mpg:tables`
- derives CRUD permission keys per protected table via `HasTableGuard::getTablePermissions()`
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

## HasTableGuard: CRUD Permissions
New method available in `HasTableGuard`:

```php
public static function getTablePermissions(
    bool $readOnly = false,
    bool $withRestore = false
): array {
    $tablePermissions = [];
    $tableName = (new static())->getTable();
    $prefix = 'dpb-mpg';

    if ($readOnly) {
        return ["{$prefix}.{$tableName}.read"];
    }

    $tablePermissions[] = "{$prefix}.{$tableName}.create";
    $tablePermissions[] = "{$prefix}.{$tableName}.read";
    $tablePermissions[] = "{$prefix}.{$tableName}.update";
    $tablePermissions[] = "{$prefix}.{$tableName}.delete";

    if ($withRestore) {
        $tablePermissions[] = "{$prefix}.{$tableName}.restore";
    }

    return $tablePermissions;
}
```

This method allows automatic generation of CRUD permissions for each guarded table.

---

## HasPageGuard & HasComponentGuard
These traits allow **automatic permission generation** for Filament Pages and Livewire Components.

### HasPageGuard
- Generates a permission key like `dpb-mpg-package.access.page-name` from the FQCN.
- Use it in Page classes to integrate with RBAC and menu registration.

```php
use Dpb\MasterPermissionGuard\Concerns\HasPageGuard;

class ExamplePage extends Page
{
    use HasPageGuard;
}
```

- Provides static `getAccessPermission()` to generate the key.
- Works with your RBAC system for menu visibility and route access.

### HasComponentGuard
- Automatically **skips rendering** of a Livewire Component if the user lacks permission.
- Example:

```php
use Dpb\MasterPermissionGuard\Concerns\HasComponentGuard;

class ExampleComponent extends Component
{
    use HasComponentGuard;
}
```

- Internally calls `skipRender()` in the trait boot method.
- Permission is generated dynamically via `getAccessPermission()` from the FQCN.

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
PermissionGuardService::authorize($table, 'read');
```

### Raw SQL
Helper (`SqlInspector`) uses `EXPLAIN <sql>` to infer target tables. Known limitations remain for statements where EXPLAIN is ambiguous.

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
A simple plugin and page are included. Page and component traits integrate directly with this RBAC system for automatic access control.

---

## Exceptions and UX
`MissingPermissionException` returns HTTP 403. Filament notifications are triggered when access is denied.

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
- **Raw SQL coverage** is partial. Prefer the query builder.
- Assumes standard Spatie setup and logged-in user via default guard.

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
- Fully automated Page & Component permission registration via traits

---

## License
`proprietary` (see `composer.json`).

