<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;

class DataPruningService
{
    /**
     * Immutable blacklist of sovereign tables that can NEVER be pruned.
     * Hardcoded safeguard to guarantee zero data loss on critical system entities.
     *
     * @var array<int, string>
     */
    private const array IMMUTABLE_PROTECTED_TABLES = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
    ];

    /**
     * Extra tables that the trashed-records tooling (inspect / hard purge) must never touch,
     * even if they happen to have a deleted_at column. Kept separate from the list above
     * because some of these (e.g. activity_log, failed_jobs) are legitimately pruned by retention rules.
     *
     * @var array<int, string>
     */
    private const array TRASH_EXCLUDED_TABLES = [
        'system_settings',
        'activity_log',
        'personal_access_tokens',
        'failed_jobs',
        'job_batches',
    ];

    /**
     * Column names that must never be returned by the trashed-records inspector.
     */
    private const string SENSITIVE_COLUMN_PATTERN = '/(password|passwd|token|secret|api_key|private_key|two_factor|recovery_code)/i';

    /**
     * Retrieve the effective configuration merged with database-stored user overrides.
     *
     * @return array<string, mixed>
     */
    public function getEffectiveConfig(): array
    {
        /** @var array<string, mixed> $baseConfig */
        $baseConfig = (array) config('pruning', []);

        // Mark built-in tables as not custom
        if (isset($baseConfig['tables']) && is_array($baseConfig['tables'])) {
            foreach ($baseConfig['tables'] as $k => $v) {
                $baseConfig['tables'][$k]['is_custom'] = false;
            }
        }

        /** @var array<string, mixed>|null $custom */
        $custom = SystemSetting::get('data_pruning_settings');

        if ($custom === null || ! is_array($custom)) {
            return $baseConfig;
        }

        $merged = $baseConfig;

        if (isset($custom['enabled'])) {
            $merged['enabled'] = (bool) $custom['enabled'];
        }

        if (isset($custom['chunk_size'])) {
            $merged['chunk_size'] = (int) $custom['chunk_size'];
        }

        if (isset($custom['tables']) && is_array($custom['tables'])) {
            foreach ($custom['tables'] as $tableKey => $tableOverrides) {
                if (isset($merged['tables'][$tableKey]) && is_array($tableOverrides)) {
                    foreach (['enabled', 'retention_days', 'max_records', 'only_read'] as $field) {
                        if (array_key_exists($field, $tableOverrides)) {
                            $merged['tables'][$tableKey][$field] = $tableOverrides[$field];
                        }
                    }
                } elseif (is_array($tableOverrides) && ! empty($tableOverrides['is_custom']) && ! empty($tableOverrides['table'])) {
                    // Custom dynamically onboarded table (only accepted when it was onboarded through addCustomTable)
                    $tableOverrides['is_custom'] = true;
                    $merged['tables'][$tableKey] = $tableOverrides;
                }
            }
        }

        return $merged;
    }

    /**
     * Save custom pruning settings to database and record an audit log.
     *
     * @param  array<string, mixed>  $settings
     */
    public function saveCustomSettings(array $settings): void
    {
        /** @var array<string, mixed> $existing */
        $existing = (array) (SystemSetting::get('data_pruning_settings') ?? []);

        // Only tables that are already configured (built-in or previously onboarded) may be saved here.
        $allowedKeys = array_map('strval', array_merge(
            array_keys((array) config('pruning.tables', [])),
            array_keys((array) ($existing['tables'] ?? []))
        ));
        foreach (array_keys((array) ($settings['tables'] ?? [])) as $submittedKey) {
            if (! in_array((string) $submittedKey, $allowedKeys, true)) {
                throw new InvalidArgumentException("Table '{$submittedKey}' is not configured for pruning and cannot be added through the settings form.");
            }
        }

        // Preserve metadata for custom dynamically added tables
        if (isset($existing['tables']) && is_array($existing['tables'])) {
            foreach ($existing['tables'] as $tblKey => $tblData) {
                if (! empty($tblData['is_custom']) && isset($settings['tables'][$tblKey])) {
                    $settings['tables'][$tblKey]['table'] = $tblData['table'] ?? $tblKey;
                    $settings['tables'][$tblKey]['primary_key'] = $tblData['primary_key'] ?? 'id';
                    $settings['tables'][$tblKey]['date_column'] = $tblData['date_column'] ?? 'created_at';
                    $settings['tables'][$tblKey]['is_custom'] = true;
                }
            }
        }

        SystemSetting::set('data_pruning_settings', $settings, 'pruning', 'Data pruning custom lifecycle parameters');

        activity('data_pruning')
            ->withProperties($settings)
            ->log('Updated automated data pruning settings');
    }

    /**
     * Reset custom pruning settings back to configuration defaults.
     */
    public function resetCustomSettings(): void
    {
        SystemSetting::forget('data_pruning_settings');

        activity('data_pruning')
            ->log('Reset automated data pruning settings to defaults');
    }

    /**
     * Determine if custom pruning settings are currently active in database storage.
     */
    public function hasCustomSettings(): bool
    {
        return SystemSetting::where('key', 'data_pruning_settings')->exists();
    }

    /**
     * Retrieve recent pruning audit activity records.
     *
     * @return Collection<int, Activity>
     */
    public function getPruningHistory(int $limit = 10): Collection
    {
        return Activity::where('log_name', 'data_pruning')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Retrieve all table names in the active database.
     *
     * @return array<int, string>
     */
    public function getAvailableDatabaseTables(): array
    {
        $schema = Schema::getFacadeRoot();

        if (is_object($schema) && method_exists($schema, 'getTables')) {
            // Laravel 11+: real tables only (views are excluded).
            /** @var array<int, array{name: string}> $rows */
            $rows = Schema::getTables();
            $tables = array_map(fn (array $row): string => (string) $row['name'], $rows);
        } elseif (DB::getDriverName() === 'sqlite') {
            $tables = Schema::getTableListing();
        } else {
            $dbName = DB::getDatabaseName();
            /** @var array<int, object> $results */
            $results = DB::select('SHOW FULL TABLES FROM `'.str_replace('`', '``', $dbName)."` WHERE Table_type = 'BASE TABLE'");
            $tables = array_map(fn (object $row): string => (string) (array_values((array) $row)[0] ?? ''), $results);
        }

        $normalized = array_map(function (string $tbl): string {
            $trimmed = trim($tbl);
            if (str_contains($trimmed, '.')) {
                $parts = explode('.', $trimmed);

                return end($parts);
            }

            return $trimmed;
        }, $tables);

        return array_values(array_unique($normalized));
    }

    /**
     * Discover database tables that are eligible to be onboarded for automated pruning.
     * Filters out sovereign protected tables and already configured tables.
     *
     * @return array<int, array{
     *     name: string,
     *     columns: array<int, string>,
     *     suggested_pk: string,
     *     suggested_date: string,
     *     count: int
     * }>
     */
    public function getEligibleTablesForPruning(): array
    {
        $allDbTables = $this->getAvailableDatabaseTables();
        $effectiveConfig = $this->getEffectiveConfig();
        $configuredTables = array_keys((array) ($effectiveConfig['tables'] ?? []));

        $eligible = [];
        foreach ($allDbTables as $tableName) {
            $cleanName = trim($tableName);

            // Exclude already configured tables or sovereign protected tables
            if (in_array($cleanName, $configuredTables, true) || $this->isTableProtected($cleanName)) {
                continue;
            }

            if (! Schema::hasTable($cleanName)) {
                continue;
            }

            $columns = Schema::getColumnListing($cleanName);
            if (empty($columns)) {
                continue;
            }

            // Suggest Primary Key
            $suggestedPk = in_array('id', $columns, true) ? 'id' : ($columns[0] ?? 'id');

            // Suggest Date Column
            $suggestedDate = null;
            foreach (['created_at', 'failed_at', 'logged_at', 'record_date', 'timestamp', 'updated_at'] as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    $suggestedDate = $candidate;
                    break;
                }
            }

            $count = 0;
            try {
                $count = DB::table($cleanName)->count();
            } catch (\Throwable) {
                // Ignore query failure on edge-case system/view tables
            }

            $eligible[] = [
                'name' => $cleanName,
                'columns' => $columns,
                'suggested_pk' => $suggestedPk,
                'suggested_date' => $suggestedDate ?? ($columns[1] ?? $suggestedPk),
                'count' => $count,
            ];
        }

        // Sort alphabetically by table name
        usort($eligible, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $eligible;
    }

    /**
     * Add a custom database table to automated data pruning.
     *
     * @param  array{
     *     table: string,
     *     primary_key: string,
     *     date_column: string,
     *     retention_days: int,
     *     max_records: int,
     *     enabled?: bool
     * }  $data
     */
    public function addCustomTable(array $data): void
    {
        $tableName = trim((string) $data['table']);

        if ($this->isTableProtected($tableName)) {
            throw new InvalidArgumentException("Security Violation: Table '{$tableName}' is sovereign/protected and cannot be added to pruning.");
        }

        if (! Schema::hasTable($tableName)) {
            throw new InvalidArgumentException("Database table '{$tableName}' does not exist.");
        }

        $columns = Schema::getColumnListing($tableName);
        $primaryKey = trim((string) ($data['primary_key'] ?? 'id'));
        $dateColumn = trim((string) ($data['date_column'] ?? 'created_at'));

        if (! in_array($primaryKey, $columns, true)) {
            throw new InvalidArgumentException("Primary key column '{$primaryKey}' does not exist on table '{$tableName}'.");
        }

        if (! in_array($dateColumn, $columns, true)) {
            throw new InvalidArgumentException("Date column '{$dateColumn}' does not exist on table '{$tableName}'.");
        }

        /** @var array<string, mixed> $custom */
        $custom = (array) (SystemSetting::get('data_pruning_settings') ?? []);
        if (! isset($custom['tables']) || ! is_array($custom['tables'])) {
            $custom['tables'] = [];
        }

        $custom['tables'][$tableName] = [
            'enabled' => (bool) ($data['enabled'] ?? true),
            'table' => $tableName,
            'primary_key' => $primaryKey,
            'date_column' => $dateColumn,
            'retention_days' => max(0, (int) ($data['retention_days'] ?? 30)),
            'max_records' => max(0, (int) ($data['max_records'] ?? 10000)),
            'is_custom' => true,
        ];

        SystemSetting::set('data_pruning_settings', $custom, 'pruning', 'Data pruning custom lifecycle parameters');

        activity('data_pruning')
            ->withProperties($custom['tables'][$tableName])
            ->log("Added custom table '{$tableName}' to automated data pruning");
    }

    /**
     * Remove a custom-added table from data pruning configuration.
     */
    public function removeCustomTable(string $table): void
    {
        $tableName = trim($table);

        // Do not allow removing default config tables
        $baseTables = array_keys((array) config('pruning.tables', []));
        if (in_array($tableName, $baseTables, true)) {
            throw new InvalidArgumentException("Built-in system table '{$tableName}' cannot be removed, but can be disabled.");
        }

        /** @var array<string, mixed>|null $custom */
        $custom = SystemSetting::get('data_pruning_settings');
        if ($custom === null || ! is_array($custom) || ! isset($custom['tables'][$tableName])) {
            throw new InvalidArgumentException("Custom table '{$tableName}' is not configured in database settings.");
        }

        unset($custom['tables'][$tableName]);

        SystemSetting::set('data_pruning_settings', $custom, 'pruning', 'Data pruning custom lifecycle parameters');

        activity('data_pruning')
            ->withProperties(['table' => $tableName])
            ->log("Removed custom table '{$tableName}' from automated data pruning");
    }

    /**
     * Check if data pruning is globally enabled in effective configuration.
     */
    public function isGloballyEnabled(): bool
    {
        $config = $this->getEffectiveConfig();

        return (bool) ($config['enabled'] ?? true);
    }

    /**
     * Determine if a given table is sovereign/protected from pruning.
     */
    public function isTableProtected(string $table): bool
    {
        $clean = strtolower(trim($table));
        if (str_contains($clean, '.')) {
            $parts = explode('.', $clean);
            $clean = end($parts);
        }

        $effectiveConfig = $this->getEffectiveConfig();

        /** @var array<int, string> $configProtected */
        $configProtected = (array) ($effectiveConfig['protected_tables'] ?? []);
        $allProtected = array_unique(array_merge(self::IMMUTABLE_PROTECTED_TABLES, $configProtected));

        return in_array($clean, array_map('strtolower', $allProtected), true);
    }

    /**
     * Prune all configured and enabled tables.
     *
     * @return array{
     *     enabled: bool,
     *     dry_run: bool,
     *     tables: array<string, array<string, mixed>>,
     *     total_pruned: int,
     *     status: string,
     *     message?: string
     * }
     */
    public function pruneAll(bool $dryRun = false, ?int $chunkSize = null): array
    {
        if (! $this->isGloballyEnabled()) {
            return [
                'enabled' => false,
                'dry_run' => $dryRun,
                'tables' => [],
                'total_pruned' => 0,
                'status' => 'disabled',
                'message' => 'Data pruning is globally disabled in configuration.',
            ];
        }

        $effectiveConfig = $this->getEffectiveConfig();

        /** @var array<string, array<string, mixed>> $tablesConfig */
        $tablesConfig = (array) ($effectiveConfig['tables'] ?? []);
        $results = [];
        $totalPruned = 0;

        foreach ($tablesConfig as $tableKey => $tableConfig) {
            if (! empty($tableConfig['enabled'])) {
                $tableResult = $this->pruneTable($tableKey, $dryRun, $chunkSize);
                $results[$tableKey] = $tableResult;
                $totalPruned += (int) ($tableResult['total_pruned'] ?? 0);
            }
        }

        return [
            'enabled' => true,
            'dry_run' => $dryRun,
            'tables' => $results,
            'total_pruned' => $totalPruned,
            'status' => 'completed',
        ];
    }

    /**
     * Prune a specific table according to its configured lifecycle rules.
     *
     * @return array{
     *     table: string,
     *     enabled: bool,
     *     dry_run: bool,
     *     date_pruned: int,
     *     count_pruned: int,
     *     total_pruned: int,
     *     remaining_records: int,
     *     status: string,
     *     message?: string
     * }
     */
    public function pruneTable(string $tableKey, bool $dryRun = false, ?int $chunkSize = null): array
    {
        $effectiveConfig = $this->getEffectiveConfig();

        /** @var array<string, array<string, mixed>> $allTables */
        $allTables = (array) ($effectiveConfig['tables'] ?? []);
        $config = null;

        if (isset($allTables[$tableKey])) {
            $config = $allTables[$tableKey];
        } else {
            foreach ($allTables as $cfg) {
                if (isset($cfg['table']) && $cfg['table'] === $tableKey) {
                    $config = $cfg;
                    break;
                }
            }
        }

        if ($config === null) {
            throw new InvalidArgumentException("Table '{$tableKey}' is not configured in pruning lifecycle rules.");
        }

        $tableName = (string) ($config['table'] ?? $tableKey);

        // Enforce Sovereign Exclusion Safeguard
        if ($this->isTableProtected($tableName)) {
            throw new InvalidArgumentException("Security Violation: Table '{$tableName}' is sovereign/protected and cannot be pruned.");
        }

        if (! Schema::hasTable($tableName)) {
            return [
                'table' => $tableName,
                'enabled' => (bool) ($config['enabled'] ?? false),
                'dry_run' => $dryRun,
                'date_pruned' => 0,
                'count_pruned' => 0,
                'total_pruned' => 0,
                'remaining_records' => 0,
                'status' => 'missing_table',
                'message' => "Database table '{$tableName}' does not exist.",
            ];
        }

        $primaryKey = (string) ($config['primary_key'] ?? 'id');
        $dateColumn = (string) ($config['date_column'] ?? 'created_at');
        $retentionDays = (int) ($config['retention_days'] ?? 0);
        $maxRecords = (int) ($config['max_records'] ?? 0);
        $batchSize = $chunkSize ?? (int) ($config['chunk_size'] ?? $effectiveConfig['chunk_size'] ?? 1000);
        $batchSize = max(1, $batchSize);

        $tableColumns = Schema::getColumnListing($tableName);
        if (! in_array($primaryKey, $tableColumns, true) || ! in_array($dateColumn, $tableColumns, true)) {
            return [
                'table' => $tableName,
                'enabled' => (bool) ($config['enabled'] ?? false),
                'dry_run' => $dryRun,
                'date_pruned' => 0,
                'count_pruned' => 0,
                'total_pruned' => 0,
                'remaining_records' => 0,
                'status' => 'invalid_config',
                'message' => "Primary key '{$primaryKey}' or date column '{$dateColumn}' does not exist on table '{$tableName}'.",
            ];
        }

        $datePruned = 0;
        $countPruned = 0;

        // 1. Date-based Retention Pruning
        if ($retentionDays > 0) {
            $cutoff = now()->subDays($retentionDays);
            $dateQuery = DB::table($tableName)->where($dateColumn, '<', $cutoff);

            // Handle table-specific filters (e.g. only prune read notifications)
            if ($tableName === 'notifications' && ! empty($config['only_read'])) {
                $dateQuery->whereNotNull('read_at');
            }

            if ($dryRun) {
                $datePruned = (clone $dateQuery)->count();
            } else {
                do {
                    $ids = (clone $dateQuery)
                        ->limit($batchSize)
                        ->pluck($primaryKey)
                        ->all();

                    if (empty($ids)) {
                        break;
                    }

                    $deleted = DB::table($tableName)->whereIn($primaryKey, $ids)->delete();
                    $datePruned += $deleted;
                } while (count($ids) >= $batchSize);
            }
        }

        // 2. Count-based Maximum Capacity Pruning
        if ($maxRecords > 0) {
            if ($dryRun) {
                $currentTotal = DB::table($tableName)->count();
                $estimatedRemaining = max(0, $currentTotal - $datePruned);
                if ($estimatedRemaining > $maxRecords) {
                    $countPruned = $estimatedRemaining - $maxRecords;
                }
            } else {
                $currentTotal = DB::table($tableName)->count();
                if ($currentTotal > $maxRecords) {
                    $excess = $currentTotal - $maxRecords;
                    $remainingToPrune = $excess;

                    while ($remainingToPrune > 0) {
                        $limit = min($remainingToPrune, $batchSize);
                        $ids = DB::table($tableName)
                            ->orderBy($dateColumn, 'asc')
                            ->orderBy($primaryKey, 'asc')
                            ->limit($limit)
                            ->pluck($primaryKey)
                            ->all();

                        if (empty($ids)) {
                            break;
                        }

                        $deleted = DB::table($tableName)->whereIn($primaryKey, $ids)->delete();
                        $countPruned += $deleted;
                        $remainingToPrune -= $deleted;

                        if ($deleted === 0) {
                            break;
                        }
                    }
                }
            }
        }

        $totalPruned = $datePruned + $countPruned;

        // 3. Audit Trail Integration (Record only on actual execution with deleted records)
        if (! $dryRun && $totalPruned > 0) {
            $this->logPruningActivity($tableName, $datePruned, $countPruned, $totalPruned, $config);
        }

        $currentDbCount = DB::table($tableName)->count();
        $remainingRecords = $dryRun ? max(0, $currentDbCount - $totalPruned) : $currentDbCount;

        return [
            'table' => $tableName,
            'enabled' => (bool) ($config['enabled'] ?? false),
            'dry_run' => $dryRun,
            'date_pruned' => $datePruned,
            'count_pruned' => $countPruned,
            'total_pruned' => $totalPruned,
            'remaining_records' => $remainingRecords,
            'status' => 'success',
        ];
    }

    /**
     * Record automated pruning action in the Spatie activity log.
     *
     * @param  array<string, mixed>  $config
     */
    protected function logPruningActivity(
        string $table,
        int $datePruned,
        int $countPruned,
        int $totalPruned,
        array $config
    ): void {
        try {
            activity('data_pruning')
                ->withProperties([
                    'table' => $table,
                    'date_pruned' => $datePruned,
                    'count_pruned' => $countPruned,
                    'total_pruned' => $totalPruned,
                    'retention_days' => $config['retention_days'] ?? null,
                    'max_records' => $config['max_records'] ?? null,
                ])
                ->log("Auto-pruned {$totalPruned} records from table '{$table}' (Date: {$datePruned}, Capacity: {$countPruned})");
        } catch (\Throwable $e) {
            Log::warning("Failed to record pruning activity log for table '{$table}': {$e->getMessage()}");
        }
    }

    /**
     * Map database table name to its corresponding Eloquent Model name.
     */
    public function resolveModelName(string $table): ?string
    {
        $map = [
            'missions' => 'Mission',
            'mission_orders' => 'MissionOrder',
            'mission_deployments' => 'MissionDeployment',
            'employees' => 'Employee',
            'equipment' => 'Equipment',
            'instruments' => 'Instrument',
            'calibration_certificates' => 'CalibrationCertificate',
            'customers' => 'Customer',
            'clients' => 'Customer',
            'sites' => 'Site',
            'contracts' => 'Contract',
            'contract_items' => 'ContractItem',
            'attachments' => 'Attachment',
            'attachment_items' => 'AttachmentItem',
            'charges' => 'Expense',
            'garanties' => 'Warranty',
            'income_forecasts' => 'IncomeForecast',
            'reports' => 'Report',
            'item_types' => 'ItemType',
            'grandeurs' => 'Grandeur',
            'chromatograph_verifications' => 'ChromatographVerification',
            'flow_computer_verifications' => 'FlowComputerVerification',
            'probe_verifications' => 'ProbeVerification',
            'prover_verifications' => 'ProverVerification',
            'prover_verification_runs' => 'ProverVerificationRun',
            'transmitter_verifications' => 'TransmitterVerification',
            'calibration_certificate_extractions' => 'CalibrationCertificateExtraction',
        ];

        return $map[$table] ?? null;
    }

    /**
     * Resolve the SoftDeletes Eloquent model class that backs a table (if known and valid).
     * Using the model lets observers, forceDeleted events and media cleanup run.
     *
     * @return class-string<Model>|null
     */
    protected function resolveSoftDeleteModel(string $table): ?string
    {
        $short = $this->resolveModelName($table);
        if ($short === null) {
            return null;
        }

        $class = 'App\\Models\\'.$short;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        if (! in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            return null;
        }

        return (new $class)->getTable() === $table ? $class : null;
    }

    /**
     * Tables excluded from every trashed-records operation (inspect / purge).
     */
    protected function isTrashExcluded(string $table): bool
    {
        return $this->isTableProtected($table)
            || in_array(strtolower(trim($table)), self::TRASH_EXCLUDED_TABLES, true);
    }

    /**
     * Resolve the real single-column primary key of a table.
     *
     * @param  array<int, string>  $columns
     *
     * @throws InvalidArgumentException when the table has a composite primary key
     */
    protected function resolvePrimaryKey(string $table, array $columns): string
    {
        try {
            foreach (Schema::getIndexes($table) as $index) {
                if (! empty($index['primary'])) {
                    $pkColumns = (array) ($index['columns'] ?? []);

                    if (count($pkColumns) !== 1) {
                        throw new InvalidArgumentException("Table '{$table}' has a composite primary key and cannot be purged row by row.");
                    }

                    return (string) $pkColumns[0];
                }
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable) {
            // Fall through to the conventional 'id' column below.
        }

        if (in_array('id', $columns, true)) {
            return 'id';
        }

        throw new InvalidArgumentException("Table '{$table}' has no usable primary key and cannot be purged safely.");
    }

    /**
     * Single place for the validation shared by every trashed-record operation.
     *
     * @return array{table: string, columns: array<int, string>, primary_key: string}
     *
     * @throws InvalidArgumentException
     */
    protected function assertSoftDeletable(string $table): array
    {
        $tableName = trim($table);

        if ($this->isTrashExcluded($tableName)) {
            throw new InvalidArgumentException("Security Violation: Table '{$tableName}' is sovereign and protected.");
        }

        if (! Schema::hasTable($tableName)) {
            throw new InvalidArgumentException("Table '{$tableName}' does not exist.");
        }

        $columns = Schema::getColumnListing($tableName);
        if (! in_array('deleted_at', $columns, true)) {
            throw new InvalidArgumentException("Table '{$tableName}' does not support soft deletes (no deleted_at column).");
        }

        return [
            'table' => $tableName,
            'columns' => $columns,
            'primary_key' => $this->resolvePrimaryKey($tableName, $columns),
        ];
    }

    protected function isForeignKeyViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'foreign key') || str_contains($message, 'integrity constraint');
    }

    /**
     * Permanently delete rows that are STILL soft-deleted. Never touches live rows,
     * even if the primary key list is stale (e.g. a record was restored meanwhile).
     *
     * @param  array<int, mixed>  $ids
     * @return array{deleted: int, blocked: int} blocked = rows refused by foreign key constraints
     *
     * @throws QueryException for any non foreign-key database error
     */
    protected function forceDeleteRows(string $table, string $primaryKey, array $ids): array
    {
        if (empty($ids)) {
            return ['deleted' => 0, 'blocked' => 0];
        }

        $deleted = 0;
        $blocked = 0;

        // Preferred path: go through Eloquent so observers / events / media cleanup run.
        $modelClass = $this->resolveSoftDeleteModel($table);
        if ($modelClass !== null) {
            $modelClass::onlyTrashed()->whereIn($primaryKey, $ids)->get()->each(
                function (Model $model) use (&$deleted, &$blocked): void {
                    try {
                        if ($model->forceDelete()) {
                            $deleted++;
                        }
                    } catch (QueryException $e) {
                        if (! $this->isForeignKeyViolation($e)) {
                            throw $e;
                        }
                        $blocked++;
                    }
                }
            );

            return ['deleted' => $deleted, 'blocked' => $blocked];
        }

        // Generic path (query builder).
        try {
            $deleted = DB::table($table)
                ->whereIn($primaryKey, $ids)
                ->whereNotNull('deleted_at')
                ->delete();
        } catch (QueryException $e) {
            if (! $this->isForeignKeyViolation($e)) {
                throw $e;
            }

            // Retry row by row so one referenced record doesn't block the whole chunk.
            foreach ($ids as $id) {
                try {
                    $deleted += DB::table($table)
                        ->where($primaryKey, $id)
                        ->whereNotNull('deleted_at')
                        ->delete();
                } catch (QueryException $inner) {
                    if (! $this->isForeignKeyViolation($inner)) {
                        throw $inner;
                    }
                    $blocked++;
                }
            }
        }

        return ['deleted' => $deleted, 'blocked' => $blocked];
    }

    /**
     * Discover database tables that feature soft-deletes (deleted_at) and calculate their trashed counts.
     * By default, tables with zero trashed records are excluded to show only actionable entities.
     *
     * @param  bool  $onlyWithTrashed  If true (default), tables with zero soft-deleted records are excluded.
     * @return array<int, array{
     *     table: string,
     *     model: string|null,
     *     trashed_count: int,
     *     total_count: int,
     *     oldest_deleted_at: string|null,
     *     newest_deleted_at: string|null,
     *     columns: array<int, string>,
     * }>
     */
    public function getTablesWithTrashedData(bool $onlyWithTrashed = true): array
    {
        $results = [];

        foreach ($this->getAvailableDatabaseTables() as $table) {
            $tableName = trim($table);

            if ($this->isTrashExcluded($tableName)) {
                continue;
            }

            try {
                $columns = Schema::getColumnListing($tableName);
                if (! in_array('deleted_at', $columns, true)) {
                    continue;
                }

                // One aggregated query per table instead of four.
                $stats = DB::table($tableName)
                    ->selectRaw('COUNT(*) AS total_count')
                    ->selectRaw('SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS trashed_count')
                    ->selectRaw('MIN(deleted_at) AS oldest_deleted_at')
                    ->selectRaw('MAX(deleted_at) AS newest_deleted_at')
                    ->first();

                $trashedCount = (int) ($stats->trashed_count ?? 0);

                if ($onlyWithTrashed && $trashedCount === 0) {
                    continue;
                }

                $results[] = [
                    'table' => $tableName,
                    'model' => $this->resolveModelName($tableName),
                    'trashed_count' => $trashedCount,
                    'total_count' => (int) ($stats->total_count ?? 0),
                    'oldest_deleted_at' => ! empty($stats->oldest_deleted_at) ? (string) $stats->oldest_deleted_at : null,
                    'newest_deleted_at' => ! empty($stats->newest_deleted_at) ? (string) $stats->newest_deleted_at : null,
                    'columns' => $columns,
                ];
            } catch (\Throwable $e) {
                Log::warning("Error checking soft-deleted records for table '{$tableName}': {$e->getMessage()}");
            }
        }

        // Tables with trashed records first (descending), then alphabetically
        usort($results, function (array $a, array $b): int {
            if ($a['trashed_count'] !== $b['trashed_count']) {
                return $b['trashed_count'] <=> $a['trashed_count'];
            }

            return strcmp($a['table'], $b['table']);
        });

        return $results;
    }

    /**
     * Retrieve soft-deleted records for inspection.
     *
     * @return array{
     *     table: string,
     *     model: string|null,
     *     primary_key: string,
     *     total_trashed: int,
     *     columns: array<int, string>,
     *     records: array<int, array<string, mixed>>,
     * }
     */
    public function getTrashedRecords(string $table, int $limit = 50, int $offset = 0): array
    {
        ['table' => $tableName, 'columns' => $columns, 'primary_key' => $primaryKey] = $this->assertSoftDeletable($table);

        $totalTrashed = DB::table($tableName)->whereNotNull('deleted_at')->count();

        // Select meaningful summary columns for inspection
        $priorityCols = [
            'id', 'name', 'full_name', 'title', 'designation', 'code', 'mission_code',
            'order_number', 'contract_number', 'report_number', 'reference', 'serial_number', 'tag_number',
            'model', 'brand', 'email', 'status', 'created_at', 'deleted_at',
        ];
        $selectedCols = array_values(array_intersect($priorityCols, $columns));

        if (empty($selectedCols)) {
            $selectedCols = array_slice($columns, 0, 8);
        }

        // Never expose credential-like columns through the inspector.
        $selectedCols = array_values(array_filter(
            $selectedCols,
            fn (string $col): bool => ! preg_match(self::SENSITIVE_COLUMN_PATTERN, $col)
        ));

        if (! in_array($primaryKey, $selectedCols, true)) {
            array_unshift($selectedCols, $primaryKey);
        }
        if (! in_array('deleted_at', $selectedCols, true)) {
            $selectedCols[] = 'deleted_at';
        }

        $records = DB::table($tableName)
            ->whereNotNull('deleted_at')
            ->orderByDesc('deleted_at')
            ->orderByDesc($primaryKey)   // stable ordering for offset pagination
            ->offset($offset)
            ->limit($limit)
            ->get($selectedCols)
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return [
            'table' => $tableName,
            'model' => $this->resolveModelName($tableName),
            'primary_key' => $primaryKey,
            'total_trashed' => $totalTrashed,
            'columns' => $selectedCols,
            'records' => $records,
        ];
    }

    /**
     * Permanently force-delete a single soft-deleted record from a table.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     table: string,
     *     id: string|int,
     * }
     */
    public function forceDeleteSingleTrashedRecord(string $table, string|int $id): array
    {
        ['table' => $tableName, 'primary_key' => $primaryKey] = $this->assertSoftDeletable($table);

        try {
            $result = $this->forceDeleteRows($tableName, $primaryKey, [$id]);
        } catch (QueryException $e) {
            Log::error("Failed to force-delete record #{$id} from '{$tableName}': {$e->getMessage()}");

            return [
                'success' => false,
                'message' => __('A database error occurred while deleting the record. Please check the application log.'),
                'table' => $tableName,
                'id' => $id,
            ];
        }

        if ($result['blocked'] > 0) {
            return [
                'success' => false,
                'message' => __('Cannot permanently delete record #:id: It is referenced by foreign keys in other active database records.', ['id' => $id]),
                'table' => $tableName,
                'id' => $id,
            ];
        }

        if ($result['deleted'] === 0) {
            return [
                'success' => false,
                'message' => __("Record #:id was not found or is not currently soft-deleted in table ':table'.", ['id' => $id, 'table' => $tableName]),
                'table' => $tableName,
                'id' => $id,
            ];
        }

        activity('data_pruning')
            ->withProperties([
                'table' => $tableName,
                'record_id' => $id,
                'action' => 'force_delete_single',
                'ip' => request()?->ip(),
            ])
            ->log("Permanently force-deleted record #{$id} from table '{$tableName}'");

        return [
            'success' => true,
            'message' => __("Record #:id permanently deleted from ':table'.", ['id' => $id, 'table' => $tableName]),
            'table' => $tableName,
            'id' => $id,
        ];
    }

    /**
     * Permanently force-delete all soft-deleted records in a specific table.
     * Records still referenced by foreign keys are skipped and reported via blocked_count.
     *
     * @return array{
     *     success: bool,
     *     table: string,
     *     purged_count: int,
     *     blocked_count: int,
     *     message: string,
     * }
     */
    public function forceDeleteAllTrashedForTable(string $table, ?int $chunkSize = 1000): array
    {
        ['table' => $tableName, 'primary_key' => $primaryKey] = $this->assertSoftDeletable($table);

        $batch = max(50, $chunkSize ?? 1000);
        $totalPurged = 0;
        $totalBlocked = 0;
        $lastKey = null;

        try {
            // Keyset iteration: always moves forward, so rows that cannot be
            // deleted (foreign keys) can never make this loop spin forever.
            do {
                $query = DB::table($tableName)
                    ->whereNotNull('deleted_at')
                    ->orderBy($primaryKey)
                    ->limit($batch);

                if ($lastKey !== null) {
                    $query->where($primaryKey, '>', $lastKey);
                }

                $ids = $query->pluck($primaryKey)->all();

                if (empty($ids)) {
                    break;
                }

                $result = $this->forceDeleteRows($tableName, $primaryKey, $ids);
                $totalPurged += $result['deleted'];
                $totalBlocked += $result['blocked'];
                $lastKey = end($ids);
            } while (count($ids) >= $batch);
        } catch (QueryException $e) {
            Log::error("Failed to purge trashed records from '{$tableName}': {$e->getMessage()}");

            return [
                'success' => false,
                'table' => $tableName,
                'purged_count' => $totalPurged,
                'blocked_count' => $totalBlocked,
                'message' => __("A database error occurred while purging ':table'. Please check the application log.", ['table' => $tableName]),
            ];
        }

        if ($totalPurged > 0 || $totalBlocked > 0) {
            activity('data_pruning')
                ->withProperties([
                    'table' => $tableName,
                    'purged_count' => $totalPurged,
                    'blocked_count' => $totalBlocked,
                    'action' => 'force_delete_table_all',
                    'ip' => request()?->ip(),
                ])
                ->log("Permanently purged {$totalPurged} soft-deleted records from '{$tableName}' ({$totalBlocked} blocked by constraints)");
        }

        $message = $totalBlocked > 0
            ? __("Purged :count soft-deleted records from ':table'; :blocked could not be deleted because they are still referenced by other records.", ['count' => $totalPurged, 'table' => $tableName, 'blocked' => $totalBlocked])
            : __("Successfully purged :count soft-deleted records from ':table'.", ['count' => $totalPurged, 'table' => $tableName]);

        return [
            'success' => true,
            'table' => $tableName,
            'purged_count' => $totalPurged,
            'blocked_count' => $totalBlocked,
            'message' => $message,
        ];
    }

    /**
     * Permanently force-delete all soft-deleted records across all eligible database tables.
     * Tables blocked by foreign keys are retried (up to 3 passes) as long as progress is made,
     * which naturally handles parent/child ordering.
     *
     * @return array{
     *     success: bool,
     *     status: string,
     *     total_purged: int,
     *     tables_purged: array<string, int>,
     *     blocked: array<string, int>,
     *     errors: array<string, string>,
     *     warnings: array<int, string>,
     *     message: string,
     * }
     */
    public function forceDeleteAllTrashedAcrossAllTables(): array
    {
        $pending = array_values(array_filter(
            $this->getTablesWithTrashedData(),
            fn (array $item): bool => $item['trashed_count'] > 0
        ));

        $totalPurged = 0;
        $tablesPurged = [];
        $blocked = [];
        $errors = [];

        for ($pass = 1; $pass <= 3 && ! empty($pending); $pass++) {
            $progress = 0;
            $retry = [];

            foreach ($pending as $item) {
                $tableName = $item['table'];

                try {
                    $res = $this->forceDeleteAllTrashedForTable($tableName);
                } catch (InvalidArgumentException $e) {
                    $errors[$tableName] = $e->getMessage();

                    continue;
                }

                if (! $res['success']) {
                    $errors[$tableName] = $res['message'];

                    continue;
                }

                $purged = (int) $res['purged_count'];
                $totalPurged += $purged;
                $progress += $purged;

                if ($purged > 0) {
                    $tablesPurged[$tableName] = ($tablesPurged[$tableName] ?? 0) + $purged;
                }

                if ((int) $res['blocked_count'] > 0) {
                    $blocked[$tableName] = (int) $res['blocked_count'];
                    $retry[] = $item;
                } else {
                    unset($blocked[$tableName]);
                }
            }

            $pending = $progress > 0 ? $retry : [];
        }

        $warnings = array_values($errors);
        foreach ($blocked as $tableName => $count) {
            $warnings[] = __("':table': :blocked records are still referenced by other records and were kept.", ['table' => $tableName, 'blocked' => $count]);
        }

        $status = empty($warnings) ? 'success' : ($totalPurged > 0 ? 'partial' : 'failed');

        activity('data_pruning')
            ->withProperties([
                'total_purged' => $totalPurged,
                'tables' => $tablesPurged,
                'blocked' => $blocked,
                'errors' => $errors,
                'status' => $status,
                'action' => 'force_delete_system_all',
                'ip' => request()?->ip(),
            ])
            ->log("System-wide permanent purge finished ({$status}): {$totalPurged} trashed records deleted across ".count($tablesPurged).' tables');

        return [
            'success' => $status !== 'failed',
            'status' => $status,
            'total_purged' => $totalPurged,
            'tables_purged' => $tablesPurged,
            'blocked' => $blocked,
            'errors' => $errors,
            'warnings' => $warnings,
            'message' => __('System-wide purge executed. Total permanently removed: :count records.', ['count' => $totalPurged]),
        ];
    }
}
