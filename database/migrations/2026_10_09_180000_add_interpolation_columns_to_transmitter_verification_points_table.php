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
        Schema::table('transmitter_verification_points', function (Blueprint $table) {
            $table->decimal('calibrator_1_correction', 12, 5)->nullable()->after('reference_value')->comment('Interpolated correction from Calibrator 1 active certificate');
            $table->decimal('corrected_reference_value', 12, 5)->nullable()->after('calibrator_1_correction')->comment('Reference value after adding Calibrator 1 correction');
            $table->decimal('calibrator_2_correction', 12, 5)->nullable()->after('measured_signal')->comment('Interpolated correction from Calibrator 2 active certificate');
            $table->decimal('corrected_signal', 12, 5)->nullable()->after('calibrator_2_correction')->comment('Measured signal (mA) after adding Calibrator 2 correction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transmitter_verification_points', function (Blueprint $table) {
            $table->dropColumn([
                'calibrator_1_correction',
                'corrected_reference_value',
                'calibrator_2_correction',
                'corrected_signal',
            ]);
        });
    }
};
