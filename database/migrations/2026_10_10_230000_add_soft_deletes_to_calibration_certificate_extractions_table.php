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
        if (Schema::hasTable('calibration_certificate_extractions') && ! Schema::hasColumn('calibration_certificate_extractions', 'deleted_at')) {
            Schema::table('calibration_certificate_extractions', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('calibration_certificate_extractions') && Schema::hasColumn('calibration_certificate_extractions', 'deleted_at')) {
            Schema::table('calibration_certificate_extractions', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
