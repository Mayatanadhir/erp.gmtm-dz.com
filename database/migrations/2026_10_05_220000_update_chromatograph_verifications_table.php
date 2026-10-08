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
        Schema::table('chromatograph_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('chromatograph_verifications', 'standard_gas_bottle_number')) {
                $table->string('standard_gas_bottle_number', 100)->nullable()->after('verification_date');
            }
            if (! Schema::hasColumn('chromatograph_verifications', 'certificate_number')) {
                $table->string('certificate_number', 100)->nullable()->after('standard_gas_bottle_number');
            }
            if (! Schema::hasColumn('chromatograph_verifications', 'reference_conditions')) {
                $table->string('reference_conditions', 50)->default('15°C / 101.325 kPa')->after('certificate_number');
            }
            if (! Schema::hasColumn('chromatograph_verifications', 'cylinder_validity_date')) {
                $table->date('cylinder_validity_date')->nullable()->after('reference_conditions');
            }
            if (! Schema::hasColumn('chromatograph_verifications', 'cylinder_pressure_bar')) {
                $table->decimal('cylinder_pressure_bar', 8, 2)->nullable()->after('cylinder_validity_date');
            }
            if (! Schema::hasColumn('chromatograph_verifications', 'repeatability_status')) {
                $table->boolean('repeatability_status')->default(false)->after('ambient_pressure');
            }
            if (! Schema::hasColumn('chromatograph_verifications', 'composition_accuracy_status')) {
                $table->boolean('composition_accuracy_status')->default(false)->after('repeatability_status');
            }
            if (! Schema::hasColumn('chromatograph_verifications', 'physical_properties_status')) {
                $table->boolean('physical_properties_status')->default(false)->after('composition_accuracy_status');
            }
        });

        if (! Schema::hasTable('chromatograph_verification_calibrators')) {
            Schema::create('chromatograph_verification_calibrators', function (Blueprint $table) {
                $table->foreignId('verification_id')->constrained('chromatograph_verifications')->cascadeOnDelete();
                $table->foreignId('calibrator_id')->constrained('equipment')->cascadeOnDelete();
                $table->unsignedTinyInteger('role')->default(1);
                $table->primary(['verification_id', 'calibrator_id', 'role'], 'pk_chromato_verif_calibrators');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chromatograph_verification_calibrators');

        Schema::table('chromatograph_verifications', function (Blueprint $table) {
            $columns = [
                'standard_gas_bottle_number',
                'certificate_number',
                'reference_conditions',
                'cylinder_validity_date',
                'cylinder_pressure_bar',
                'repeatability_status',
                'composition_accuracy_status',
                'physical_properties_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('chromatograph_verifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
