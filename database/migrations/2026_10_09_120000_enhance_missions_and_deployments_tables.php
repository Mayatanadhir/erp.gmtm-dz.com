<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('missions')) {
            Schema::table('missions', function (Blueprint $table): void {
                $table->decimal('mob_dmob_days', 4, 1)->default(0.0)->change();
            });
        }

        if (Schema::hasTable('mission_deployments') && ! Schema::hasColumn('mission_deployments', 'deleted_at')) {
            Schema::table('mission_deployments', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mission_deployments') && Schema::hasColumn('mission_deployments', 'deleted_at')) {
            Schema::table('mission_deployments', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('missions')) {
            Schema::table('missions', function (Blueprint $table): void {
                $table->unsignedSmallInteger('mob_dmob_days')->default(0)->change();
            });
        }
    }
};
