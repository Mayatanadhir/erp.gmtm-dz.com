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
        Schema::table('calibration_interpolations', function (Blueprint $table): void {
            $table->dropUnique('cal_interp_cert_idx_unique');
            $table->foreignId('equipment_specification_id')
                ->nullable()
                ->after('calibration_certificate_id')
                ->constrained('equipment_specifications')
                ->nullOnDelete();

            $table->index(['calibration_certificate_id', 'equipment_specification_id', 'point_index'], 'cal_interp_cert_spec_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calibration_interpolations', function (Blueprint $table): void {
            $table->dropIndex('cal_interp_cert_spec_idx');
            $table->dropConstrainedForeignId('equipment_specification_id');
            $table->unique(['calibration_certificate_id', 'point_index'], 'cal_interp_cert_idx_unique');
        });
    }
};
