<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionDiscoveryService
{
    /**
     * Strict system blacklist of internal forensic, queue, and RBAC tables.
     *
     * @var array<int, string>
     */
    public const array SYSTEM_BLACKLIST = [
        'migrations',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'failed_jobs',
        'job_batches',
        'activity_log',
        'notifications',
        'system_settings',
        'roles',
        'permissions',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'permission_categories',
    ];

    /**
     * Standard CRUD action verbs.
     *
     * @var array<int, string>
     */
    public const array CRUD_ACTIONS = [
        'view',
        'create',
        'edit',
        'delete',
    ];

    /**
     * الأدوار الخارقة التي تتجاوز كل الصلاحيات
     *
     * @var array<int, string>
     */
    protected array $superRoles = ['Super-Admin'];

    /**
     * الدور الافتراضي الأساسي الممنوح تلقائياً للمستخدمين الجدد
     */
    protected string $defaultRole = 'User';

    /**
     * Get the list of super roles.
     *
     * @return array<int, string>
     */
    public function getSuperRoles(): array
    {
        $configuredRoles = (array) config('permissions.roles', []);
        $supers = [];

        foreach ($configuredRoles as $roleName => $meta) {
            if (! empty($meta['is_super'])) {
                $supers[] = (string) $roleName;
            }
        }

        return ! empty($supers) ? $supers : $this->superRoles;
    }

    /**
     * Check if a given role name is a super role.
     */
    public function isSuperRole(string $roleName): bool
    {
        return in_array($roleName, $this->getSuperRoles(), true);
    }

    /**
     * Get the name of the default baseline role.
     */
    public function getDefaultRole(): string
    {
        $configuredRoles = (array) config('permissions.roles', []);

        foreach ($configuredRoles as $roleName => $meta) {
            if (! empty($meta['is_default'])) {
                return (string) $roleName;
            }
        }

        return $this->defaultRole;
    }

    /**
     * Check if a given role name is the system default baseline role.
     */
    public function isDefaultRole(string $roleName): bool
    {
        return $roleName === $this->getDefaultRole();
    }

    /**
     * Get the translated functional title for a given role name.
     */
    public function getRoleFunctionalTitle(string $roleName): string
    {
        $titleKey = config("permissions.roles.{$roleName}.title");

        if ($titleKey) {
            return __($titleKey);
        }

        return match ($roleName) {
            'Super-Admin' => __('Super Administrator (Full Sovereign Access)'),
            'Admin' => __('System Administrator (Operations & Users)'),
            'User' => __('Standard User (General Workspace Services)'),
            default => __($roleName),
        };
    }

    /**
     * Get the translated functional scope description for a given role name.
     */
    public function getRoleFunctionalScope(string $roleName): string
    {
        $scopeKey = config("permissions.roles.{$roleName}.scope");

        if ($scopeKey) {
            return __($scopeKey);
        }

        return match ($roleName) {
            'Super-Admin' => __('Full sovereign authority over system configurations, forensic tables, and all application data.'),
            'Admin' => __('Operational and user management with access to standard business tables, excluding security-critical tables.'),
            'User' => __('Access to general application tools and profile workspace without administrative privileges.'),
            default => __('Custom user role with assigned granular system privileges.'),
        };
    }

    /**
     * Get the configured static entities from the permissions registry.
     *
     * @return array<int, string>
     */
    public function getDiscoveredTables(?string $connection = null): array
    {
        $groups = (array) config('permissions.groups', []);
        $modules = (array) config('permissions.modules', []);
        $discovered = [];

        foreach ($groups as $groupKey => $group) {
            $entity = (string) ($group['entity'] ?? str_replace('_management', '', (string) $groupKey));
            $discovered[] = strtolower($entity);
        }

        // Also register module-level view permission entities (e.g. "metrology", "operations").
        foreach ($modules as $modKey => $modMeta) {
            $viewPerm = (string) ($modMeta['view_permission'] ?? '');
            if ($viewPerm !== '' && preg_match('/^view\s+(.+)$/i', $viewPerm, $m)) {
                $discovered[] = strtolower(trim($m[1]));
            }
        }

        $discovered = array_values(array_unique($discovered));
        sort($discovered);

        return $discovered;
    }

    /**
     * Remove standard CRUD permissions for entities that no longer exist in the configured static registry.
     *
     * @param  array<int, string>  $validTables
     * @return array<int, string> List of pruned permission names
     */
    public function pruneStaleCrudPermissions(array $validTables): array
    {
        $allPermissions = Permission::all();
        $pruned = [];

        $configuredPerms = [];
        foreach ((array) config('permissions.groups', []) as $group) {
            foreach ((array) ($group['perms'] ?? []) as $perm) {
                $configuredPerms[] = (string) $perm;
            }
        }
        foreach ((array) config('permissions.modules', []) as $module) {
            foreach ((array) ($module['perms'] ?? []) as $perm) {
                $configuredPerms[] = (string) $perm;
            }
        }

        foreach ($allPermissions as $permission) {
            $name = (string) $permission->name;

            if (in_array($name, $configuredPerms, true)) {
                continue;
            }

            if (preg_match('/^(view|create|edit|delete)\s+(.+)$/i', $name, $matches)) {
                $entity = strtolower(trim($matches[2]));

                if (! in_array($entity, $validTables, true)) {
                    $permission->delete();
                    $pruned[] = $name;
                }
            }
        }

        return $pruned;
    }

    /**
     * Generate standard permissions from the static catalog and prune obsolete permissions.
     *
     * @param  array<int, string>|null  $tables
     * @return array{
     *     tables_scanned: array<int, string>,
     *     permissions_created: array<int, string>,
     *     permissions_pruned: array<int, string>,
     *     total_active_permissions: int
     * }
     */
    public function generateCrudPermissionsForTables(?array $tables = null): array
    {
        $groups = (array) config('permissions.groups', []);
        $tablesToProcess = $tables ?? $this->getDiscoveredTables();
        $pruned = $this->pruneStaleCrudPermissions($tablesToProcess);
        $created = [];

        foreach ($groups as $group) {
            $perms = (array) ($group['perms'] ?? []);
            foreach ($perms as $permName) {
                $permission = Permission::firstOrCreate([
                    'name' => (string) $permName,
                    'guard_name' => 'web',
                ]);

                if ($permission->wasRecentlyCreated) {
                    $created[] = (string) $permName;
                }
            }
        }

        // Also create module-level view permissions (e.g. "view metrology").
        $modules = (array) config('permissions.modules', []);
        foreach ($modules as $modMeta) {
            $modPerms = (array) ($modMeta['perms'] ?? []);
            foreach ($modPerms as $permName) {
                $permission = Permission::firstOrCreate([
                    'name' => (string) $permName,
                    'guard_name' => 'web',
                ]);

                if ($permission->wasRecentlyCreated) {
                    $created[] = (string) $permName;
                }
            }
        }

        $missingSuperRole = false;
        foreach ($this->getSuperRoles() as $superRoleName) {
            if (! Role::where('name', $superRoleName)->exists()) {
                $missingSuperRole = true;
                break;
            }
        }

        if (! empty($created) || ! empty($pruned) || $missingSuperRole) {
            // Flush Spatie permission cache to ensure instant activation
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            // Ensure Super-Admin and super roles inherit all active permissions
            $this->syncSuperAdminPermissions();
        }

        return [
            'tables_scanned' => $tablesToProcess,
            'permissions_created' => $created,
            'permissions_pruned' => $pruned,
            'total_active_permissions' => Permission::count(),
        ];
    }

    /**
     * Ensure the Super-Admin and all super roles exist and hold all permissions in the system.
     */
    public function syncSuperAdminPermissions(): int
    {
        $allPermissions = Permission::all();

        foreach ($this->getSuperRoles() as $superRoleName) {
            $role = Role::firstOrCreate([
                'name' => $superRoleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($allPermissions);
        }

        return $allPermissions->count();
    }

    /**
     * Build an interactive permission matrix grouped by entity and CRUD actions.
     * Automatically introspects schema and ensures permissions exist for all discovered tables.
     *
     * @param  bool  $autoSync  Whether to automatically generate missing permissions for discovered tables
     * @return array{
     *     modules: array<string, array{
     *         key: string,
     *         name: string,
     *         icon: string,
     *         color: string,
     *         entities: array<string, array{
     *             display_name: string,
     *             icon: string,
     *             actions: array<string, string>
     *         }>,
     *         all_permissions: array<int, string>
     *     }>,
     *     entities: array<string, array<string, string>>,
     *     custom: array<int, string>,
     *     actions: array<int, string>,
     *     all_names: array<int, string>
     * }
     */
    public function getGroupedPermissionMatrix(bool $autoSync = true): array
    {
        if ($autoSync) {
            $this->generateCrudPermissionsForTables();
        }

        $permissions = Permission::orderBy('name')->get();
        $entities = [];
        $custom = [];
        $allNames = [];

        $discoveredTables = $this->getDiscoveredTables();

        foreach ($permissions as $perm) {
            $name = (string) $perm->name;
            $allNames[] = $name;

            if (preg_match('/^(view|create|edit|delete)\s+(.+)$/i', $name, $matches)) {
                $action = strtolower($matches[1]);
                $entity = strtolower(trim($matches[2]));

                // Only group entities registered in the static permissions catalog
                if (! in_array($entity, $discoveredTables, true)) {
                    $custom[] = $name;

                    continue;
                }

                if (! isset($entities[$entity])) {
                    $entities[$entity] = [];
                }
                $entities[$entity][$action] = $name;
            } else {
                $custom[] = $name;
            }
        }

        ksort($entities);
        sort($custom);

        $modulesConfig = (array) config('permissions.modules', []);
        $groupsConfig = (array) config('permissions.groups', []);

        $entityMeta = [];
        foreach ($groupsConfig as $groupKey => $group) {
            $ent = strtolower((string) ($group['entity'] ?? str_replace('_management', '', (string) $groupKey)));
            $mod = (string) ($group['module'] ?? 'other');
            $entityMeta[$ent] = [
                'module' => $mod,
                'lang' => (string) ($group['lang'] ?? $ent),
                'icon' => (string) ($group['icon'] ?? ''),
            ];
        }

        $groupedByModule = [];
        foreach ($modulesConfig as $modKey => $modMeta) {
            $groupedByModule[$modKey] = [
                'key' => (string) $modKey,
                'name' => (string) ($modMeta['name'] ?? $modKey),
                'icon' => (string) ($modMeta['icon'] ?? ''),
                'color' => (string) ($modMeta['color'] ?? 'gray'),
                'view_permission' => (string) ($modMeta['view_permission'] ?? ''),
                'entities' => [],
                'all_permissions' => [],
            ];
        }

        foreach ($entities as $entity => $actions) {
            $modKey = $entityMeta[$entity]['module'] ?? 'other';
            if (! isset($groupedByModule[$modKey])) {
                $groupedByModule[$modKey] = [
                    'key' => (string) $modKey,
                    'name' => ucfirst((string) $modKey),
                    'icon' => '',
                    'color' => 'gray',
                    'view_permission' => '',
                    'entities' => [],
                    'all_permissions' => [],
                ];
            }

            $groupedByModule[$modKey]['entities'][$entity] = [
                'display_name' => $entityMeta[$entity]['lang'] ?? $entity,
                'icon' => $entityMeta[$entity]['icon'] ?? '',
                'actions' => $actions,
            ];

            foreach ($actions as $actPerm) {
                $groupedByModule[$modKey]['all_permissions'][] = $actPerm;
            }
        }

        // Prepend the module-level view permission (e.g. "view metrology") to all_permissions
        // so that "Toggle Category" also toggles the top-level module access permission.
        foreach ($groupedByModule as $modKey => $modData) {
            $viewPerm = (string) ($modData['view_permission'] ?? '');
            if ($viewPerm !== '' && ! empty($modData['entities'])) {
                array_unshift($groupedByModule[$modKey]['all_permissions'], $viewPerm);
            }
        }

        $groupedByModule = array_filter($groupedByModule, fn (array $m): bool => ! empty($m['entities']));

        return [
            'modules' => $groupedByModule,
            'entities' => $entities,
            'custom' => $custom,
            'actions' => self::CRUD_ACTIONS,
            'all_names' => $allNames,
        ];
    }
}
