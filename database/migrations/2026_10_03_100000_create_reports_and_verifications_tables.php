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
        // 1. reports table
        if (! Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mission_id')->nullable()->constrained('missions')->onUpdate('cascade')->nullOnDelete();
                $table->string('report_number', 255)->unique();
                $table->enum('status', ['progress', 'completed'])->default('progress');
                $table->json('excluded_instrument_ids')->nullable();
                $table->json('default_calibrators')->nullable()->comment('Default reference calibrators assigned per instrument category');
                $table->timestamps();
            });
        }

        // 2. transmitter_verifications table
        if (! Schema::hasTable('transmitter_verifications')) {
            Schema::create('transmitter_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
                $table->foreignId('report_mission_id')->constrained('reports')->cascadeOnDelete();
                $table->date('verification_date')->nullable();
                $table->decimal('ambient_temperature', 5, 2)->nullable()->comment('Ambient Temperature °C');
                $table->decimal('ambient_pressure', 7, 2)->nullable()->comment('Ambient Pressure mbar');
                $table->boolean('overall_status')->nullable();
                $table->timestamps();

                $table->index(['report_mission_id', 'instrument_id'], 'idx_trans_verif_report_inst');
            });
        }

        // 3. transmitter_verification_points table
        if (! Schema::hasTable('transmitter_verification_points')) {
            Schema::create('transmitter_verification_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verification_id')->constrained('transmitter_verifications')->cascadeOnDelete()->cascadeOnUpdate();
                $table->unsignedTinyInteger('step_order')->comment('Chronological step order');
                $table->enum('cycle_phase', ['Ascending', 'Descending'])->comment('Ascending or Descending phase');
                $table->decimal('applied_percentage', 5, 2)->comment('Applied percentage of span');
                $table->decimal('reference_value', 12, 5)->comment('Reference setpoint value');
                $table->decimal('measured_signal', 12, 5)->nullable()->comment('Output signal in mA');
                $table->decimal('indicated_value', 12, 5)->nullable()->comment('Digital indicated reading');
                $table->decimal('absolute_error', 12, 5)->nullable();
                $table->decimal('emt_limit', 12, 5)->nullable();
                $table->boolean('is_conforme')->nullable();
                $table->timestamps();

                $table->unique(['verification_id', 'step_order'], 'uq_transmitter_verification_step');
                $table->index('verification_id', 'idx_trans_points_verif_id');
            });
        }

        // 4. transmitter_verification_calibrators table
        if (! Schema::hasTable('transmitter_verification_calibrators')) {
            Schema::create('transmitter_verification_calibrators', function (Blueprint $table) {
                $table->foreignId('verification_id')->constrained('transmitter_verifications')->cascadeOnDelete();
                $table->foreignId('calibrator_id')->constrained('equipment')->cascadeOnDelete();
                $table->unsignedTinyInteger('role')->default(1)->comment('1 = Calibrator 1 (Applied Reference), 2 = Calibrator 2 (Measured Signal)');
                $table->primary(['verification_id', 'role']);
                $table->index('calibrator_id', 'idx_trans_calibrator_id');
            });
        }

        // 5. probe_verifications table
        if (! Schema::hasTable('probe_verifications')) {
            Schema::create('probe_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
                $table->foreignId('report_mission_id')->constrained('reports')->cascadeOnDelete();
                $table->date('verification_date')->nullable();
                $table->decimal('ambient_temperature', 5, 2)->nullable()->comment('Ambient Temperature °C');
                $table->decimal('ambient_pressure', 7, 2)->nullable()->comment('Ambient Pressure mbar');
                $table->boolean('overall_status')->nullable();
                $table->timestamps();

                $table->index(['report_mission_id', 'instrument_id'], 'idx_probe_verif_report_inst');
            });
        }

        // 6. probe_verification_points table
        if (! Schema::hasTable('probe_verification_points')) {
            Schema::create('probe_verification_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verification_id')->constrained('probe_verifications')->cascadeOnDelete()->cascadeOnUpdate();
                $table->unsignedTinyInteger('step_order')->comment('Chronological step order');
                $table->enum('cycle_phase', ['Ascending', 'Descending'])->comment('Ascending or Descending phase');
                $table->decimal('reference_temperature', 12, 5)->comment('Reference temperature °C');
                $table->decimal('measured_resistance', 12, 5)->nullable()->comment('Measured resistance Ohm');
                $table->decimal('indicated_temperature', 12, 5)->nullable()->comment('Indicated temperature °C');
                $table->decimal('absolute_error', 12, 5)->nullable();
                $table->decimal('emt_limit', 12, 5)->nullable();
                $table->boolean('is_conforme')->nullable();
                $table->timestamps();

                $table->unique(['verification_id', 'step_order'], 'uq_probe_verification_step');
                $table->index('verification_id', 'idx_probe_points_verif_id');
            });
        }

        // 7. probe_verification_calibrators table
        if (! Schema::hasTable('probe_verification_calibrators')) {
            Schema::create('probe_verification_calibrators', function (Blueprint $table) {
                $table->foreignId('verification_id')->constrained('probe_verifications')->cascadeOnDelete();
                $table->foreignId('calibrator_id')->constrained('equipment')->cascadeOnDelete();
                $table->unsignedTinyInteger('role')->default(1)->comment('1 = Calibrator 1 (Thermal Bath/Dry Block), 2 = Calibrator 2 (Ohm Multi-meter)');
                $table->primary(['verification_id', 'role']);
                $table->index('calibrator_id', 'idx_probe_calibrator_id');
            });
        }

        // 8. flow_computer_verifications table
        if (! Schema::hasTable('flow_computer_verifications')) {
            Schema::create('flow_computer_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
                $table->foreignId('simulated_transmitter_id')->nullable()->constrained('instruments')->nullOnDelete();
                $table->decimal('shunt_resistance', 10, 2)->default(250.00)->comment('Standard shunt resistance Ohm');
                $table->foreignId('report_mission_id')->constrained('reports')->cascadeOnDelete();
                $table->date('verification_date')->nullable();
                $table->decimal('ambient_temperature', 5, 2)->nullable()->comment('Ambient Temperature °C');
                $table->decimal('ambient_pressure', 7, 2)->nullable()->comment('Ambient Pressure mbar');
                $table->boolean('overall_status')->nullable();
                $table->timestamps();

                $table->index(['report_mission_id', 'instrument_id'], 'idx_fc_verif_report_inst');
                $table->index('simulated_transmitter_id', 'idx_fc_sim_transmitter');
            });
        }

        // 9. flow_computer_verification_points table
        if (! Schema::hasTable('flow_computer_verification_points')) {
            Schema::create('flow_computer_verification_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('verification_id')->constrained('flow_computer_verifications')->cascadeOnDelete()->cascadeOnUpdate();
                $table->unsignedTinyInteger('step_order')->comment('Chronological step order');
                $table->enum('cycle_phase', ['Ascending', 'Descending'])->comment('Ascending or Descending phase');
                $table->decimal('applied_percentage', 5, 2)->comment('Applied percentage of span');
                $table->decimal('expected_signal', 12, 5)->nullable()->comment('Theoretical electrical signal mA or V');
                $table->decimal('measured_signal', 12, 5)->nullable()->comment('Actual generated signal mA or V');
                $table->decimal('expected_value', 12, 5)->nullable()->comment('Theoretical physical value');
                $table->decimal('indicated_value', 12, 5)->nullable()->comment('Value displayed on Flow Computer');
                $table->decimal('absolute_error', 12, 5)->nullable();
                $table->decimal('emt_limit', 12, 5)->nullable();
                $table->boolean('is_conforme')->nullable();
                $table->timestamps();

                $table->unique(['verification_id', 'step_order'], 'uq_flow_computer_verification_step');
                $table->index('verification_id', 'idx_fc_points_verif_id');
            });
        }

        // 10. flow_computer_verification_calibrators table
        if (! Schema::hasTable('flow_computer_verification_calibrators')) {
            Schema::create('flow_computer_verification_calibrators', function (Blueprint $table) {
                $table->foreignId('verification_id')->constrained('flow_computer_verifications')->cascadeOnDelete();
                $table->foreignId('calibrator_id')->constrained('equipment')->cascadeOnDelete();
                $table->unsignedTinyInteger('role')->default(1)->comment('1 = Calibrator 1 (mA/V Injector), 2 = Calibrator 2 (Process Variable Meter)');
                $table->primary(['verification_id', 'role']);
                $table->index('calibrator_id', 'idx_fc_calibrator_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_computer_verification_calibrators');
        Schema::dropIfExists('flow_computer_verification_points');
        Schema::dropIfExists('flow_computer_verifications');
        Schema::dropIfExists('probe_verification_calibrators');
        Schema::dropIfExists('probe_verification_points');
        Schema::dropIfExists('probe_verifications');
        Schema::dropIfExists('transmitter_verification_calibrators');
        Schema::dropIfExists('transmitter_verification_points');
        Schema::dropIfExists('transmitter_verifications');
        Schema::dropIfExists('reports');
    }
};
