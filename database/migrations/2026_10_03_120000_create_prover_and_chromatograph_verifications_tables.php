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
        // 1. chromatograph_verifications table
        if (! Schema::hasTable('chromatograph_verifications')) {
            Schema::create('chromatograph_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('reference_number', 255)->nullable()->index()->comment('OAM Reference');
                $table->foreignId('report_mission_id')->nullable()->constrained('reports')->nullOnDelete();
                $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
                $table->date('verification_date');
                $table->string('gas_bottle_number', 100)->nullable()->comment('N° de la bouteille étalon');
                $table->date('gas_bottle_expiry')->nullable()->comment('Date d\'expiration du gaz');
                $table->decimal('gas_bottle_pressure', 8, 2)->nullable()->comment('Pression bouteille bar');
                $table->decimal('ambient_temperature', 8, 2)->nullable()->comment('Température ambiante °C');
                $table->decimal('ambient_pressure', 8, 2)->nullable()->comment('Pression barométrique mbar');
                $table->boolean('overall_status')->default(false)->comment('المطابقة العامة: 1 = Conforme, 0 = Non-conforme');
                $table->text('remarks')->nullable()->comment('ملاحظات الفحص');
                $table->timestamps();
            });
        }

        // 2. chromatograph_composition_points table
        if (! Schema::hasTable('chromatograph_composition_points')) {
            Schema::create('chromatograph_composition_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verification_id')->constrained('chromatograph_verifications')->cascadeOnDelete();
                $table->unsignedTinyInteger('step_order')->comment('ترتيب المركب 1-11');
                $table->string('component_name', 50)->comment('اسم المركب');
                $table->string('component_symbol', 15)->comment('رمز المركب');
                $table->decimal('reference_value', 8, 4)->comment('القيمة المرجعية % mol/mol');
                $table->decimal('run_1', 8, 4)->nullable();
                $table->decimal('run_2', 8, 4)->nullable();
                $table->decimal('run_3', 8, 4)->nullable();
                $table->decimal('run_4', 8, 4)->nullable();
                $table->decimal('run_5', 8, 4)->nullable();
                $table->decimal('mean_value', 8, 4)->nullable()->comment('متوسط التحاليل الخمسة');
                $table->decimal('repeatability', 8, 4)->nullable()->comment('تكرارية Max - Min');
                $table->decimal('repeatability_limit_astm', 8, 4)->nullable()->comment('حد التكرارية وفق ASTM D 1945');
                $table->boolean('repeatability_is_conforme')->default(false);
                $table->decimal('relative_error_percent', 8, 3)->nullable()->comment('خطأ الدقة النسبي المئوي');
                $table->decimal('emt_limit_percent', 8, 2)->nullable()->comment('حد الخطأ المسموح به وفق ISO 6974-2');
                $table->boolean('error_is_conforme')->default(false);
                $table->boolean('is_conforme')->default(false)->comment('المطابقة النهائية للمركب');
                $table->timestamps();

                $table->unique(['verification_id', 'step_order'], 'uq_chromato_comp_step');
            });
        }

        // 3. chromatograph_physical_properties table
        if (! Schema::hasTable('chromatograph_physical_properties')) {
            Schema::create('chromatograph_physical_properties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verification_id')->constrained('chromatograph_verifications')->cascadeOnDelete();
                $table->string('property_name', 100)->comment('اسم الخاصية');
                $table->string('property_symbol', 20)->comment('رمز الخاصية');
                $table->string('unit', 30)->comment('الوحدة الفيزيائية');
                $table->decimal('reference_value', 18, 12)->nullable();
                $table->decimal('run_1', 18, 12)->nullable();
                $table->decimal('run_2', 18, 12)->nullable();
                $table->decimal('run_3', 18, 12)->nullable();
                $table->decimal('run_4', 18, 12)->nullable();
                $table->decimal('run_5', 18, 12)->nullable();
                $table->decimal('mean_value', 18, 12)->nullable();
                $table->decimal('relative_error_percent', 8, 3)->nullable()->comment('خطأ الدقة النسبي المئوي %');
                $table->decimal('emt_limit_percent', 8, 2)->nullable()->comment('حد الخطأ وفق OIML R 140 الفئة A');
                $table->decimal('repeatability', 8, 3)->nullable()->comment('تكرارية %');
                $table->decimal('repeatability_limit', 8, 3)->nullable()->comment('حد التكرارية');
                $table->boolean('is_conforme')->default(false);
                $table->timestamps();

                $table->unique(['verification_id', 'property_symbol'], 'uq_chromato_prop_symbol');
            });
        }

        // 4. prover_verifications table
        if (! Schema::hasTable('prover_verifications')) {
            Schema::create('prover_verifications', function (Blueprint $table) {
                $table->id();
                $table->date('calibration_date')->index();
                $table->string('reference_number', 255)->nullable()->comment('رقم مرجع الفحص OAM Reference');
                $table->decimal('reference_temperature', 8, 4)->default(20.0000);
                $table->string('pressure_unit', 10)->default('bar');
                $table->text('remarks')->nullable();
                $table->foreignId('prover_id')->nullable()->constrained('instruments')->nullOnDelete();
                $table->foreignId('jauge_id')->nullable()->constrained('instruments')->nullOnDelete();
                $table->decimal('base_prover_volume', 16, 5)->nullable();
                $table->decimal('max_run_volume', 16, 5)->nullable();
                $table->decimal('min_run_volume', 16, 5)->nullable();
                $table->decimal('repeatability_percent', 8, 4)->nullable();
                $table->boolean('is_conforme')->default(false)->index();
                $table->timestamps();
            });
        }

        // 5. prover_verification_runs table
        if (! Schema::hasTable('prover_verification_runs')) {
            Schema::create('prover_verification_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prover_verification_id')->constrained('prover_verifications')->cascadeOnDelete();
                $table->unsignedSmallInteger('run_number');
                $table->unsignedSmallInteger('fill_number')->default(1);
                $table->decimal('scale_reading_mm', 8, 2)->nullable();
                $table->decimal('indicated_volume', 14, 5);
                $table->decimal('gauge_temperature', 8, 4);
                $table->decimal('prover_temperature', 8, 4);
                $table->decimal('shaft_temperature', 8, 4)->nullable();
                $table->decimal('prover_pressure', 8, 4)->default(0.0000);
                $table->decimal('c_tdw', 12, 8)->nullable();
                $table->decimal('c_tsm', 12, 8)->nullable();
                $table->decimal('c_tsp', 12, 8)->nullable();
                $table->decimal('c_psp', 12, 8)->nullable();
                $table->decimal('c_plp', 12, 8)->nullable();
                $table->decimal('corrected_volume', 16, 5)->nullable();
                $table->timestamps();

                $table->index(['prover_verification_id', 'run_number', 'fill_number'], 'pvr_verif_run_fill_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prover_verification_runs');
        Schema::dropIfExists('prover_verifications');
        Schema::dropIfExists('chromatograph_physical_properties');
        Schema::dropIfExists('chromatograph_composition_points');
        Schema::dropIfExists('chromatograph_verifications');
    }
};
