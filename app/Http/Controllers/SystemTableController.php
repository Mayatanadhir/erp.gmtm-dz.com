<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Http\Requests\AddPruningTableRequest;
use App\Http\Requests\CreatePermissionRequest;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\CreateSystemUserRequest;
use App\Http\Requests\InitialSystemSetupRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Requests\UpdatePruningSettingsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\UpdateSystemUserRequest;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\DataPruningService;
use App\Services\MediaOptimizationService;
use App\Services\PermissionDiscoveryService;
use App\Services\SystemTableService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemTableController extends Controller
{
    public function __construct(
        protected SystemTableService $systemTableService,
        protected DataPruningService $dataPruningService,
        protected DatabaseBackupService $databaseBackupService,
        protected UserService $userService,
        protected PermissionDiscoveryService $permissionDiscoveryService
    ) {}

    /**
     * Display the System Tables Explorer overview dashboard.
     */
    public function index(): View
    {
        $stats = $this->systemTableService->getOverviewStats();
        $recentActivities = $this->systemTableService->getRecentActivities(6);

        return view('system.index', compact('stats', 'recentActivities'));
    }

    /**
     * Display Users and Active Sessions.
     */
    public function users(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $users = $this->systemTableService->getUsers(
            is_string($search) ? $search : null,
            is_string($status) ? $status : null
        );
        $sessions = $this->systemTableService->getSessions();
        $roles = $this->systemTableService->getRoles();
        $registrationOpen = is_registration_open();

        return view('system.users', compact('users', 'sessions', 'search', 'status', 'roles', 'registrationOpen'));
    }

    /**
     * Create a new user from the system explorer.
     */
    public function storeUser(CreateSystemUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! empty($validated['status'])) {
            $userData['status'] = $validated['status'];
        }

        if ($request->hasFile('photo')) {
            $userData['profile_photo_path'] = app(MediaOptimizationService::class)->optimizeAvatar($request->file('photo'), 'photos', 'public');
        } elseif (array_key_exists('profile_photo_path', $validated)) {
            $userData['profile_photo_path'] = $validated['profile_photo_path'];
        }

        $user = $this->userService->register($userData);

        if (! empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        activity('system_users')
            ->performedOn($user)
            ->withProperties([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status?->value ?? 'active',
                'role' => $validated['role'] ?? null,
            ])
            ->log("Created new system user '{$user->name}'");

        return redirect()
            ->route('system-tables.users', $request->query())
            ->with('status', __('User :name created successfully.', ['name' => $user->name]));
    }

    /**
     * Update an existing user from the system explorer.
     */
    public function updateUser(UpdateSystemUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        // Anti-lockout guard: Do not allow admin to suspend their own account
        if (
            $user->id === auth()->id() &&
            ! empty($validated['status']) &&
            $validated['status'] === AccountStatus::Suspended->value
        ) {
            return redirect()
                ->route('system-tables.users', $request->query())
                ->withErrors(['status' => __('You cannot suspend your own account.')]);
        }

        // Rank-lock guard: Do not allow admin to change their own role
        if ($user->id === auth()->id() && array_key_exists('role', $validated)) {
            return redirect()
                ->route('system-tables.users', $request->query())
                ->withErrors(['role' => __('You cannot change your own role.')]);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if (! empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path) {
                app(MediaOptimizationService::class)->safeDelete($user->profile_photo_path, 'public', $user->id);
            }
            $updateData['profile_photo_path'] = app(MediaOptimizationService::class)->optimizeAvatar($request->file('photo'), 'photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            if ($user->profile_photo_path) {
                app(MediaOptimizationService::class)->safeDelete($user->profile_photo_path, 'public', $user->id);
            }
            $updateData['profile_photo_path'] = null;
        } elseif (array_key_exists('profile_photo_path', $validated)) {
            $updateData['profile_photo_path'] = $validated['profile_photo_path'];
        }

        $user->update($updateData);

        if (array_key_exists('role', $validated)) {
            $roleToAssign = ! empty($validated['role'])
                ? $validated['role']
                : $this->permissionDiscoveryService->getDefaultRole();

            $user->syncRoles([$roleToAssign]);
        }

        activity('system_users')
            ->performedOn($user)
            ->withProperties([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status?->value ?? 'active',
                'role' => $validated['role'] ?? null,
            ])
            ->log("Updated system user '{$user->name}'");

        return redirect()
            ->route('system-tables.users', $request->query())
            ->with('status', __('User :name updated successfully.', ['name' => $user->name]));
    }

    /**
     * Toggle active/suspended status of a user.
     */
    public function toggleUserStatus(User $user): RedirectResponse
    {
        // Anti-lockout guard: prevent suspending own account
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('system-tables.users', request()->query())
                ->withErrors(['error' => __('You cannot suspend your own account.')]);
        }

        $newStatus = $user->isActive() ? AccountStatus::Suspended : AccountStatus::Active;
        $user->update(['status' => $newStatus]);

        activity('system_users')
            ->performedOn($user)
            ->withProperties([
                'user_id' => $user->id,
                'name' => $user->name,
                'previous_status' => $user->getOriginal('status'),
                'new_status' => $newStatus->value,
            ])
            ->log("Changed user status to '{$newStatus->value}' for '{$user->name}'");

        return redirect()
            ->route('system-tables.users', request()->query())
            ->with('status', __('User :name status changed to :status.', [
                'name' => $user->name,
                'status' => $newStatus->label(),
            ]));
    }

    /**
     * Delete a user account from the system explorer.
     */
    public function destroyUser(User $user): RedirectResponse
    {
        // Anti-lockout guard: Do not allow admin to delete their own account
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('system-tables.users', request()->query())
                ->withErrors(['error' => __('You cannot delete your own account.')]);
        }

        $userName = $user->name;

        activity('system_users')
            ->performedOn($user)
            ->withProperties([
                'user_id' => $user->id,
                'name' => $userName,
                'email' => $user->email,
            ])
            ->log("Deleted system user '{$userName}'");

        $user->delete();

        return redirect()
            ->route('system-tables.users', request()->query())
            ->with('status', __('User :name deleted successfully.', ['name' => $userName]));
    }

    /**
     * Display Roles and Permissions matrix.
     */
    public function roles(): View
    {
        $permissionMatrix = $this->permissionDiscoveryService->getGroupedPermissionMatrix(autoSync: true);
        $roles = $this->systemTableService->getRoles();
        $permissions = $this->systemTableService->getPermissions();
        $allPermissions = Permission::orderBy('name')->get();
        $superRoles = $this->permissionDiscoveryService->getSuperRoles();
        $defaultRole = $this->permissionDiscoveryService->getDefaultRole();
        $discoveryService = $this->permissionDiscoveryService;

        return view('system.roles', compact('roles', 'permissions', 'allPermissions', 'permissionMatrix', 'superRoles', 'defaultRole', 'discoveryService'));
    }

    /**
     * Create a new role with assigned permissions.
     */
    public function storeRole(CreateRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        activity('roles_permissions')
            ->performedOn($role)
            ->withProperties([
                'role_id' => $role->id,
                'name' => $role->name,
                'permissions' => $validated['permissions'] ?? [],
            ])
            ->log("Created new system role '{$role->name}'");

        return redirect()
            ->route('system-tables.roles')
            ->with('status', __('Role :name created successfully.', ['name' => $role->name]));
    }

    /**
     * Create a new permission in the system catalog.
     */
    public function storePermission(CreatePermissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        activity('roles_permissions')
            ->performedOn($permission)
            ->withProperties([
                'permission_id' => $permission->id,
                'name' => $permission->name,
            ])
            ->log("Created new system permission '{$permission->name}'");

        return redirect()
            ->route('system-tables.roles')
            ->with('status', __('Permission :name created successfully.', ['name' => $permission->name]));
    }

    /**
     * Update an existing role and its assigned permissions.
     */
    public function updateRole(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if ($this->permissionDiscoveryService->isSuperRole($role->name)) {
            return redirect()
                ->route('system-tables.roles')
                ->withErrors(['role' => __('The Super-Admin role is protected by the anti-lockout mechanism and cannot be modified.')]);
        }

        $validated = $request->validated();

        if ($this->permissionDiscoveryService->isDefaultRole($role->name) && $validated['name'] !== $role->name) {
            return redirect()
                ->route('system-tables.roles')
                ->withErrors(['role' => __('The default role cannot be renamed.')]);
        }

        $oldName = $role->name;
        $role->update([
            'name' => $validated['name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        activity('roles_permissions')
            ->performedOn($role)
            ->withProperties([
                'role_id' => $role->id,
                'old_name' => $oldName,
                'new_name' => $role->name,
                'permissions' => $validated['permissions'] ?? [],
            ])
            ->log("Updated system role '{$role->name}'");

        return redirect()
            ->route('system-tables.roles')
            ->with('status', __('Role :name updated successfully.', ['name' => $role->name]));
    }

    /**
     * Update an existing permission in the system catalog.
     */
    public function updatePermission(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validated();

        $oldName = $permission->name;
        $permission->update([
            'name' => $validated['name'],
        ]);

        activity('roles_permissions')
            ->performedOn($permission)
            ->withProperties([
                'permission_id' => $permission->id,
                'old_name' => $oldName,
                'new_name' => $permission->name,
            ])
            ->log("Updated system permission '{$permission->name}'");

        return redirect()
            ->route('system-tables.roles')
            ->with('status', __('Permission :name updated successfully.', ['name' => $permission->name]));
    }

    /**
     * Delete a role from the system RBAC catalog.
     */
    public function destroyRole(Role $role): RedirectResponse
    {
        if ($this->permissionDiscoveryService->isSuperRole($role->name)) {
            return redirect()
                ->route('system-tables.roles')
                ->withErrors(['role' => __('The Super-Admin role is protected by the anti-lockout mechanism and cannot be deleted.')]);
        }

        if ($this->permissionDiscoveryService->isDefaultRole($role->name)) {
            return redirect()
                ->route('system-tables.roles')
                ->withErrors(['role' => __('The default role cannot be deleted.')]);
        }

        $roleName = $role->name;

        activity('roles_permissions')
            ->performedOn($role)
            ->withProperties([
                'role_id' => $role->id,
                'name' => $roleName,
                'users_count' => $role->users()->count(),
            ])
            ->log("Deleted system role '{$roleName}'");

        $role->delete();

        return redirect()
            ->route('system-tables.roles')
            ->with('status', __('Role :name deleted successfully.', ['name' => $roleName]));
    }

    /**
     * Delete a permission from the system access catalog.
     */
    public function destroyPermission(Permission $permission): RedirectResponse
    {
        $permissionName = $permission->name;

        activity('roles_permissions')
            ->performedOn($permission)
            ->withProperties([
                'permission_id' => $permission->id,
                'name' => $permissionName,
            ])
            ->log("Deleted system permission '{$permissionName}'");

        $permission->delete();

        return redirect()
            ->route('system-tables.roles')
            ->with('status', __('Permission :name deleted successfully.', ['name' => $permissionName]));
    }

    /**
     * Display the Activity Log audit trail.
     */
    public function activityLog(Request $request): View
    {
        $event = $request->query('event');
        $search = $request->query('search');

        $activities = $this->systemTableService->getActivityLogs(
            is_string($event) ? $event : null,
            is_string($search) ? $search : null
        );

        return view('system.activity-log', compact('activities', 'event', 'search'));
    }

    /**
     * Display Notifications.
     */
    public function notifications(Request $request): View
    {
        $status = $request->query('status');
        $notifications = $this->systemTableService->getNotifications(
            is_string($status) ? $status : null
        );

        return view('system.notifications', compact('notifications', 'status'));
    }

    /**
     * Display Queues, Failed Jobs, and Job Batches.
     */
    public function queues(Request $request): View
    {
        $tab = $request->query('tab', 'jobs');
        $jobs = $this->systemTableService->getJobs();
        $failedJobs = $this->systemTableService->getFailedJobs();
        $batches = $this->systemTableService->getJobBatches();

        return view('system.queues', compact('jobs', 'failedJobs', 'batches', 'tab'));
    }

    /**
     * Display Cache entries and Cache Locks.
     */
    public function cache(): View
    {
        $cacheEntries = $this->systemTableService->getCacheEntries();
        $cacheLocks = $this->systemTableService->getCacheLocks();

        return view('system.cache', compact('cacheEntries', 'cacheLocks'));
    }

    /**
     * Display Data Pruning and Lifecycle Management settings dashboard.
     */
    public function pruningSettings(): View
    {
        $effectiveConfig = $this->dataPruningService->getEffectiveConfig();
        $hasCustomSettings = $this->dataPruningService->hasCustomSettings();
        $history = $this->dataPruningService->getPruningHistory(10)->map(function ($item) {
            $item->translated_description = $this->systemTableService->translateActivityDescription($item->description);

            return $item;
        });
        $eligibleTables = $this->dataPruningService->getEligibleTablesForPruning();

        $counts = [];
        foreach (array_keys((array) ($effectiveConfig['tables'] ?? [])) as $tblKey) {
            $counts[$tblKey] = $this->systemTableService->safeCount($tblKey);
        }

        return view('system.pruning', array_merge(
            compact('effectiveConfig', 'hasCustomSettings', 'history', 'counts', 'eligibleTables'),
            $this->trashedOverview()
        ));
    }

    /**
     * Update custom pruning lifecycle settings in database storage.
     */
    public function updatePruningSettings(UpdatePruningSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'enabled' => $request->has('enabled') ? $request->boolean('enabled') : true,
            'chunk_size' => (int) $validated['chunk_size'],
            'tables' => [],
        ];

        foreach ($validated['tables'] as $tableKey => $tableData) {
            $data['tables'][$tableKey] = [
                'enabled' => ! empty($tableData['enabled']),
                'retention_days' => (int) $tableData['retention_days'],
                'max_records' => (int) $tableData['max_records'],
            ];

            if ($tableKey === 'notifications') {
                $data['tables'][$tableKey]['only_read'] = $request->boolean('tables.notifications.only_read');
            }
        }

        try {
            $this->dataPruningService->saveCustomSettings($data);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('system-tables.pruning')
                ->withErrors(['tables' => $e->getMessage()]);
        }

        return redirect()
            ->route('system-tables.pruning')
            ->with('status', __('Pruning lifecycle settings updated successfully.'));
    }

    /**
     * Run a dry-run pruning simulation and return simulated results.
     */
    public function dryRunPruning(Request $request): JsonResponse|RedirectResponse
    {
        $report = $this->dataPruningService->pruneAll(dryRun: true);

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        return redirect()
            ->route('system-tables.pruning')
            ->with('dry_run_report', $report);
    }

    /**
     * Trigger immediate automated pruning execution.
     */
    public function executePruning(): RedirectResponse
    {
        $report = $this->dataPruningService->pruneAll(dryRun: false);

        return redirect()
            ->route('system-tables.pruning')
            ->with('status', __('Automated pruning executed. Total records purged: :count', ['count' => $report['total_pruned']]));
    }

    /**
     * Reset custom pruning settings back to configuration defaults.
     */
    public function resetPruningSettings(): RedirectResponse
    {
        $this->dataPruningService->resetCustomSettings();

        return redirect()
            ->route('system-tables.pruning')
            ->with('status', __('Pruning settings have been reset to configuration defaults.'));
    }

    /**
     * Add a dynamic database table to automated data pruning.
     */
    public function addPruningTable(AddPruningTableRequest $request): RedirectResponse
    {
        try {
            /** @var array{table: string, primary_key: string, date_column: string, retention_days: int, max_records: int, enabled?: bool} $data */
            $data = $request->validated();
            $this->dataPruningService->addCustomTable($data);

            return redirect()
                ->route('system-tables.pruning')
                ->with('status', __('Table :table successfully onboarded to automated pruning.', ['table' => $request->validated('table')]));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('system-tables.pruning')
                ->withErrors(['table' => $e->getMessage()]);
        }
    }

    /**
     * Remove a custom table from automated data pruning.
     */
    public function removePruningTable(string $table): RedirectResponse
    {
        try {
            $this->dataPruningService->removeCustomTable($table);

            return redirect()
                ->route('system-tables.pruning')
                ->with('status', __('Table :table successfully removed from automated pruning.', ['table' => $table]));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('system-tables.pruning')
                ->withErrors(['table' => $e->getMessage()]);
        }
    }

    /**
     * Trashed tables + their total, computed once for every view that needs it.
     *
     * @return array{trashedTables: array<int, array<string, mixed>>, totalTrashedCount: int}
     */
    private function trashedOverview(): array
    {
        $trashedTables = $this->dataPruningService->getTablesWithTrashedData();

        return [
            'trashedTables' => $trashedTables,
            'totalTrashedCount' => (int) array_sum(array_column($trashedTables, 'trashed_count')),
        ];
    }

    /**
     * Optional permission gate for the trashed-records tooling.
     * Set config('pruning.purge_permission') to a permission name to enforce it; null disables the check
     * (route middleware may already protect these routes).
     */
    private function authorizePurge(): void
    {
        $permission = config('pruning.purge_permission');

        if (is_string($permission) && $permission !== '') {
            abort_unless(Auth::user()?->can($permission), 403);
        }
    }

    private function backToTrashed(?string $status = null, ?string $error = null): RedirectResponse
    {
        $response = redirect()->back(fallback: route('system-tables.trashed'));

        if ($status !== null) {
            $response->with('status', $status);
        }

        if ($error !== null) {
            $response->withErrors(['trashed' => $error]);
        }

        return $response;
    }

    /**
     * Display dedicated Trashed Records & Hard Purge dashboard.
     */
    public function trashedIndex(): View
    {
        $this->authorizePurge();

        return view('system.trashed', $this->trashedOverview());
    }

    /**
     * Inspect soft-deleted records for a given database table via JSON.
     */
    public function getTrashedRecords(Request $request, string $table): JsonResponse
    {
        $this->authorizePurge();

        try {
            $limit = min(100, max(1, (int) $request->input('limit', 50)));
            $offset = max(0, (int) $request->input('offset', 0));

            return response()->json($this->dataPruningService->getTrashedRecords($table, $limit, $offset));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error("Trashed records inspection failed for '{$table}': {$e->getMessage()}");

            return response()->json(['error' => __('Failed to retrieve soft-deleted records.')], 500);
        }
    }

    /**
     * Display dedicated full-page preview for soft-deleted records of a specific table.
     */
    public function trashedShow(Request $request, string $table): View|RedirectResponse
    {
        $this->authorizePurge();

        try {
            $trashedData = $this->dataPruningService->getTrashedRecords($table, 100);

            return view('system.trashed-show', compact('trashedData'));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('system-tables.trashed')
                ->withErrors(['trashed' => $e->getMessage()]);
        }
    }

    /**
     * Permanently purge a single soft-deleted record.
     */
    public function purgeSingleTrashedRecord(Request $request, string $table, string|int $id): JsonResponse|RedirectResponse
    {
        $this->authorizePurge();

        try {
            $result = $this->dataPruningService->forceDeleteSingleTrashedRecord($table, $id);
        } catch (\InvalidArgumentException $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error("Single trashed purge failed for '{$table}' #{$id}: {$e->getMessage()}");
            $result = ['success' => false, 'message' => __('Request failed. Check server logs.')];
        }

        if ($request->wantsJson()) {
            return response()->json(
                $result + ['error' => $result['success'] ? null : $result['message']],
                $result['success'] ? 200 : 422
            );
        }

        return $result['success']
            ? $this->backToTrashed(status: $result['message'])
            : $this->backToTrashed(error: $result['message']);
    }

    /**
     * Permanently purge ALL soft-deleted records for a specific table.
     */
    public function purgeTableTrashed(Request $request, string $table): RedirectResponse
    {
        $this->authorizePurge();

        $confirmation = strtoupper(trim((string) $request->input('confirmation', '')));
        if ($confirmation !== 'PURGE') {
            return $this->backToTrashed(error: __('Security confirmation failed: You must type :expected to confirm table purge.', ['expected' => 'PURGE']));
        }

        try {
            $result = $this->dataPruningService->forceDeleteAllTrashedForTable($table);
        } catch (\InvalidArgumentException $e) {
            return $this->backToTrashed(error: $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Table trashed purge failed for '{$table}': {$e->getMessage()}");

            return $this->backToTrashed(error: __('Request failed. Check server logs.'));
        }

        // Partial results (rows kept because of foreign keys) are shown as a warning, not as a success.
        return ($result['success'] && $result['blocked_count'] === 0)
            ? $this->backToTrashed(status: $result['message'])
            : $this->backToTrashed(error: $result['message']);
    }

    /**
     * Permanently purge ALL soft-deleted records across all database tables.
     */
    public function purgeAllTrashed(Request $request): RedirectResponse
    {
        $this->authorizePurge();

        $confirmation = strtoupper(trim((string) $request->input('confirmation', '')));
        if ($confirmation !== 'FORCE DELETE') {
            return $this->backToTrashed(error: __('Security confirmation failed: You must type :expected to confirm system-wide purge.', ['expected' => 'FORCE DELETE']));
        }

        try {
            $result = $this->dataPruningService->forceDeleteAllTrashedAcrossAllTables();
        } catch (\Throwable $e) {
            Log::error("System-wide trashed purge failed: {$e->getMessage()}");

            return $this->backToTrashed(error: __('Request failed. Check server logs.'));
        }

        $warnings = $result['warnings'] === [] ? null : implode(' | ', $result['warnings']);

        return $result['success']
            ? $this->backToTrashed(status: $result['message'], error: $warnings)
            : $this->backToTrashed(error: $warnings ?? $result['message']);
    }

    /**
     * Display Database Backups management dashboard.
     */
    public function backups(): View
    {
        $backupData = $this->databaseBackupService->getBackups();

        return view('system.backups', compact('backupData'));
    }

    /**
     * Create a new database backup snapshot.
     */
    public function createBackup(): RedirectResponse
    {
        try {
            $result = $this->databaseBackupService->createBackup(onlyDb: true);

            return redirect()
                ->route('system-tables.backups')
                ->with('status', $result['message']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('system-tables.backups')
                ->withErrors(['backup' => $e->getMessage()]);
        }
    }

    /**
     * Download a database backup snapshot.
     */
    public function downloadBackup(string $file): BinaryFileResponse|RedirectResponse
    {
        try {
            $cleanName = $this->databaseBackupService->sanitizeFileName($file);
            $relativePath = $this->databaseBackupService->resolveBackupRelativePath($cleanName);
            $fullPath = Storage::disk('local')->path($relativePath);

            return response()->download($fullPath, $cleanName, [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Throwable $e) {
            return redirect()
                ->route('system-tables.backups')
                ->withErrors(['backup' => $e->getMessage()]);
        }
    }

    /**
     * Delete a database backup snapshot.
     */
    public function deleteBackup(string $file): RedirectResponse
    {
        try {
            $this->databaseBackupService->deleteBackup($file);

            return redirect()
                ->route('system-tables.backups')
                ->with('status', __('Backup snapshot :file successfully deleted.', ['file' => $file]));
        } catch (\Throwable $e) {
            return redirect()
                ->route('system-tables.backups')
                ->withErrors(['backup' => $e->getMessage()]);
        }
    }

    /**
     * Restore database from a specific backup snapshot.
     */
    public function restoreBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'string'],
        ]);

        $file = (string) $request->input('file');

        try {
            $result = $this->databaseBackupService->restoreBackup($file);

            return redirect()
                ->route('system-tables.backups')
                ->with('status', $result['message']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('system-tables.backups')
                ->withErrors(['backup' => $e->getMessage()]);
        }
    }

    /**
     * Restore database from the oldest available backup snapshot.
     */
    public function restoreOldestBackup(): RedirectResponse
    {
        try {
            $result = $this->databaseBackupService->restoreOldestBackup();

            return redirect()
                ->route('system-tables.backups')
                ->with('status', $result['message']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('system-tables.backups')
                ->withErrors(['backup' => $e->getMessage()]);
        }
    }

    /**
     * Display the initial Super Admin setup screen when the system has no users or unmigrated schema.
     */
    public function setup(): View
    {
        $needsMigration = ! Schema::hasTable('users');

        if (! $needsMigration && User::count() > 0) {
            abort(404);
        }

        return view('system.setup', compact('needsMigration'));
    }

    /**
     * Provision the first Super Admin account and initialize system state.
     */
    public function storeSetup(InitialSystemSetupRequest $request): RedirectResponse
    {
        if (Schema::hasTable('users') && User::count() > 0) {
            abort(404);
        }

        $user = $this->systemTableService->createInitialSuperAdmin($request->validated());

        Auth::login($user);
        $request->session()->save();

        if (env('SESSION_DRIVER') === 'database' && Schema::hasTable('sessions')) {
            try {
                DB::table('sessions')->updateOrInsert(
                    ['id' => $request->session()->getId()],
                    [
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 500),
                        'payload' => base64_encode(serialize($request->session()->all())),
                        'last_activity' => time(),
                    ]
                );
            } catch (\Throwable) {
                // Fallback gracefully
            }
        }

        return redirect()
            ->route('dashboard')
            ->with('status', __('System initialized successfully! Welcome to ENGI-GMTM.'));
    }

    /**
     * Display Dynamic System Settings dashboard.
     */
    public function settings(): View
    {
        $registrationOpen = is_registration_open();
        $settings = SystemSetting::orderBy('group')->orderBy('key')->get();

        return view('system.settings', compact('registrationOpen', 'settings'));
    }

    /**
     * Toggle the system registration status.
     */
    public function toggleRegistration(Request $request): JsonResponse|RedirectResponse
    {
        $current = is_registration_open();
        $newValue = $request->has('enabled')
            ? filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN)
            : ! $current;

        SystemSetting::set(
            'allow_registration',
            $newValue,
            'auth',
            'Allow new user registrations on the site'
        );

        $stateLabel = $newValue ? 'enabled' : 'disabled';
        $logMessage = "Changed registration status to {$stateLabel}";

        activity('system_settings')
            ->causedBy(auth()->user())
            ->withProperties([
                'setting' => 'allow_registration',
                'previous_value' => $current,
                'new_value' => $newValue,
            ])
            ->log($logMessage);

        $message = $newValue
            ? __('New user registration has been enabled successfully.')
            : __('New user registration has been disabled successfully.');

        if ($request->expectsJson() || $request->isJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'enabled' => $newValue,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('status', $message);
    }

    /**
     * Dynamic update for arbitrary system settings.
     */
    public function updateSetting(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:191',
            'value' => 'required',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $key = $validated['key'];

        // Keys managed by dedicated, validated flows can never be overwritten through this generic endpoint.
        $reservedKeys = ['data_pruning_settings'];
        /** @var array<int, string> $editableKeys */
        $editableKeys = (array) config('system.editable_settings', []);

        if (in_array($key, $reservedKeys, true) || ($editableKeys !== [] && ! in_array($key, $editableKeys, true))) {
            throw ValidationException::withMessages([
                'key' => __('This setting cannot be modified through this form.'),
            ]);
        }

        $value = $validated['value'];
        $group = $validated['group'] ?? 'general';
        $description = $validated['description'] ?? null;

        $previous = SystemSetting::get($key);

        SystemSetting::set($key, $value, $group, $description);

        activity('system_settings')
            ->causedBy(auth()->user())
            ->withProperties([
                'setting' => $key,
                'previous_value' => $previous,
                'new_value' => $value,
                'group' => $group,
            ])
            ->log("Updated system setting '{$key}'");

        $message = __('Setting :key updated successfully.', ['key' => $key]);

        if ($request->expectsJson() || $request->isJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'key' => $key,
                'value' => $value,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('status', $message);
    }
}
