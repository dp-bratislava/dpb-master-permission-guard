<?php

namespace Dpb\MasterPermissionGuard\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;
use Dpb\MasterPermissionGuard\Concerns\HasTableGuard;
use Dpb\MasterPermissionGuard\Support\Registry;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class DiscoverProtectedModels extends Command
{
    private const TRAIT = HasTableGuard::class;
    private const CACHE_KEY = 'mpg:tables';
    private const DEFAULT_GUARDS = ['web'];
    private const DEFAULT_PERMISSIONS = ['create', 'read', 'update', 'delete'];

    protected $signature = 'dpb:mpg:discover
        {--paths=* : Paths to scan (PSR-4 roots).}
        {--packages=* : Vendor packages vendor/name (limits scan).}
        {--dry : Print results only; no writes, no permissions}';

    protected $description = 'Finds models using '.self::TRAIT.' and stores a table ⇒ FQCN map in cache.';

    public function handle(): int
    {
        $hits = $this->collectProtectedModels();
        $this->info('Protected tables: '.count($hits));
        if ($this->option('dry')) {
            foreach ($hits as $tableName => $tableConfig) {
                $this->line(sprintf(
                    " - %s => %s [%s], guards: %s",
                    $tableConfig['class'],
                    $tableName,
                    implode(', ', $tableConfig['permissions']),
                    implode(', ', $tableConfig['guards'])
                ));
            }
            $this->warn('DRY-RUN: Did not write anything to cache or create permissions.');
            return self::SUCCESS;
        }

        // Save into cache and load into RAM
        cache()->forever(self::CACHE_KEY, $hits);
        Registry::seed($hits);

        if (class_exists(Permission::class) && app()->bound(PermissionRegistrar::class)) {
            $this->info('Spatie permissions package detected, syncing permissions…');
            $this->syncPermissions($hits);
        } else {
            $this->warn('Spatie permissions package not found, skipping permission sync.');
        }

        return self::SUCCESS;
    }

    private function syncPermissions(array $hits): void
    {
        $guard = config('permission.defaults.guard', config('auth.defaults.guard', 'web'));

        foreach ($hits as $table => $tableInfo) {
            foreach ($tableInfo['guards'] as $guard) {
                foreach ($tableInfo['permissions'] ?? [] as $op) {
                    $permissionKey = "dpb-mpg.{$table}.{$op}";
                    $this->info("Syncing permission: {$permissionKey} for guard: {$guard}");
                    Permission::findOrCreate($permissionKey, $guard);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('Permissions seeded.');
    }

    private function collectProtectedModels(): array
    {
        $roots = $this->resolveRoots();
        $hits  = [];

        foreach ($roots as $root) {
            $modelsDir = rtrim($root, '/');
            if (!is_dir($modelsDir)) {
                continue;
            }

            // only classes in ./src/Models/
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelsDir));
            $it  = new RegexIterator($rii, '/'.preg_quote(DIRECTORY_SEPARATOR, '/').'Models'.preg_quote(DIRECTORY_SEPARATOR, '/').'.*\.php$/i');

            foreach ($it as $file) {
                $path = $file->getPathname();
                if (strpos(file_get_contents($path) ?: '', self::TRAIT) === false) {
                    continue;
                }

                $fqcn = $this->fqcnFromFile($path);

                if (!$fqcn || !class_exists($fqcn)) {
                    continue;
                }

                if (!is_subclass_of($fqcn, Model::class)) {
                    continue;
                }

                // ignore abstract classes
                $rc = new ReflectionClass($fqcn);
                if ($rc->isAbstract()) {
                    continue;
                }

                // really uses trait?
                $uses = class_uses_recursive($fqcn);
                if (!in_array(self::TRAIT, $uses, true)) {
                    continue;
                }

                // get table name
                /** @var \Illuminate\Database\Eloquent\Model $m */
                $m = $fqcn::query()->getModel();
                $hits[$m->getTable()] = [
                    'class' => $fqcn,
                    'guards' => self::DEFAULT_GUARDS,
                    'table' => $m->getTable(),
                    'permissions' => array_merge(
                        self::DEFAULT_PERMISSIONS,
                        in_array(SoftDeletes::class, $uses, true) ? ['restore'] : []
                    )
                ];
            }
        }
        return $hits;
    }

    /** @return array<int,string> */
    private function resolveRoots(): array
    {
        $roots = [];

        // user-specified
        $opt = $this->option('paths');
        if (is_array($opt) && $opt) {
            foreach ($opt as $p) {
                if (is_dir($p)) {
                    $roots[] = $p;
                }
            }
        }

        // app/
        $roots[] = app_path();

        // local packages
        foreach ($this->localPackageRootsRequiringMPG('packages') as $p) {
            $roots[] = $p;
        }

        // vendor packages
        $wanted = array_filter((array) $this->option('packages'));
        foreach ($this->vendorPackageRootsRequiringMPG($wanted) as $p) {
            $roots[] = $p;
        }

        return array_values(array_unique($roots));
    }

    private function localPackageRootsRequiringMPG(
        string $base = 'packages'
    ): array {
        $roots = [];
        $our = 'dpb/dpb-master-permission-guard';

        foreach (array_merge(
            glob(base_path("$base/*/*"), GLOB_ONLYDIR) ?: [],
            glob(base_path("$base/*"), GLOB_ONLYDIR) ?: []
        ) as $pkgDir) {
            $cmp = "$pkgDir/composer.json";
            if (!is_file($cmp)) {
                continue;
            }

            $j = json_decode((string)file_get_contents($cmp), true);
            $req = array_keys(($j['require'] ?? []) + ($j['require-dev'] ?? []));
            if (!in_array($our, $req, true)) {
                continue;
            }

            $psr4 = $j['autoload']['psr-4'] ?? [];
            $paths = [];

            foreach ($psr4 as $ns => $dirs) {
                foreach ((array)$dirs as $d) {
                    $paths[] = rtrim("$pkgDir/$d", '/');
                }
            }
            if (!$paths && is_dir("$pkgDir/src")) {
                $paths[] = "$pkgDir/src";
            }

            foreach ($paths as $p) {
                if (is_dir($p)) {
                    $roots[] = $p;
                }
            }
        }
        return array_values(array_unique($roots));
    }


    /** @return array<int,string> roots pod vendor/…/src */
    private function vendorPackageRootsRequiringMPG(
        array $only = []
    ): array {
        $installedPath = base_path('vendor/composer/installed.php');
        if (!is_file($installedPath)) {
            return [];
        }

        $data = require $installedPath;
        $versions = $data['versions'] ?? $data ?? [];
        $roots = [];
        $ourPkg = 'dpb/dpb-model-permission-guard';

        foreach ($versions as $name => $info) {
            if (!is_array($info)) {
                continue;
            }
            if ($only && !in_array($name, $only, true)) {
                continue;
            }

            $requires = ($info['require'] ?? []) + ($info['require-dev'] ?? []);
            $needs = $only ? true : isset($requires[$ourPkg]);
            if (!$needs) {
                continue;
            }

            $root = rtrim((string)($info['install_path'] ?? ''), '/');
            if ($root && is_dir($root.'/src')) {
                $roots[] = $root.'/src';
            }
        }
        return $roots;
    }

    private function fqcnFromFile(
        string $path
    ): ?string {
        $code = file_get_contents($path);
        if ($code === false) {
            return null;
        }

        $ns = '';
        $class = null;
        $tokens = token_get_all($code);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $ns = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === ';') {
                        break;
                    }
                    $ns .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                }
                $ns = trim($ns);
            }
            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }

        if (!$class) {
            return null;
        }
        return ltrim($ns.'\\'.$class, '\\');
    }
}
