<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibration_interpolations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calibration_certificate_id')->constrained('calibration_certificates')->cascadeOnDelete();
            $table->unsignedTinyInteger('point_index')->comment('1 to 5 standard grid index');
            $table->double('target_nominal')->comment('Target point nominal value (X_k)');
            $table->double('interpolated_value')->comment('Calculated interpolated value / correction (Y_k)');
            $table->double('experimental_uncertainty')->comment('Propagated experimental uncertainty (u_exp)');
            $table->double('curvature_coefficient')->nullable()->comment('Parabolic curvature coefficient (a_2)');
            $table->double('modeling_uncertainty')->default(0.0)->comment('Geometric modeling uncertainty (u_mod)');
            $table->double('combined_uncertainty')->comment('Combined standard uncertainty (u_c)');
            $table->double('expanded_uncertainty')->comment('Expanded uncertainty (U, k=2)');
            $table->boolean('is_exact_point')->default(false)->comment('True if point coincides with exact calibrated point');
            $table->timestamps();

            $table->unique(['calibration_certificate_id', 'point_index'], 'cal_interp_cert_idx_unique');
            $table->index(['calibration_certificate_id', 'target_nominal'], 'cal_interp_cert_nom_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_interpolations');
    }
};
