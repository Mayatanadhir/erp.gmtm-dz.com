<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The eligible tables to standardize with soft deletes.
     *
     * @var array<int, string>
     */
    private const array ELIGIBLE_TABLES = [
        'customers',
        'sites',
        'contracts',
        'contract_items',
        'attachments',
        'attachment_items',
        'charges',
        'garanties',
        'income_forecasts',
        'reports',
        'chromatograph_verifications',
        'flow_computer_verifications',
        'probe_verifications',
        'prover_verifications',
        'prover_verification_runs',
        'transmitter_verifications',
        'item_types',
        'grandeurs',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::ELIGIBLE_TABLES as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::ELIGIBLE_TABLES as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
