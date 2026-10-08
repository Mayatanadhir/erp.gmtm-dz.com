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
        // 1. جدول أجهزة القياس الصناعية الرئيسي
        Schema::create('instruments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('tag_number', 100)->index();
            $table->string('serial_number', 100)->unique();
            $table->string('instrument_type', 50)->index();
            $table->string('process_variable', 50)->nullable()->index();
            $table->string('measurement_type', 100)->nullable();
            $table->string('fluid_type', 50)->nullable();
            $table->string('technology', 100)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('image_hash', 64)->nullable()->index();
            $table->string('status', 50)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. جدول المواصفات الفيزيائية ونطاقات القياس
        Schema::create('instrument_specifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
            $table->foreignId('grandeur_id')->constrained('grandeurs')->cascadeOnDelete();
            $table->decimal('range_min', 14, 5)->nullable();
            $table->decimal('range_max', 14, 5)->nullable();
            $table->decimal('accuracy_value', 10, 4)->nullable();
            $table->string('accuracy_type', 20)->nullable();
            $table->timestamps();

            $table->unique(['instrument_id', 'grandeur_id'], 'inst_spec_unique');
        });

        // 3. جدول وساطة قنوات حاسبة التدفق والمرسلات التابعة
        Schema::create('flow_computer_transmitter', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('flow_computer_id')->constrained('instruments')->cascadeOnDelete();
            $table->foreignId('transmitter_id')->constrained('instruments')->cascadeOnDelete();
            $table->unsignedInteger('channel_number')->default(1);
            $table->timestamps();

            $table->unique(['flow_computer_id', 'transmitter_id', 'channel_number'], 'fc_trans_channel_unique');
        });

        // 4. جدول المواصفات المترولوجية لمقياس السعة العياري (Jauge étalon)
        Schema::create('standard_gauge_specifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instrument_id')->unique()->constrained('instruments')->cascadeOnDelete();
            $table->decimal('nominal_capacity_liters', 14, 5);
            $table->decimal('neck_scale_sensitivity', 10, 5)->nullable();
            $table->decimal('cubical_expansion_coef_gcm', 14, 8)->nullable();
            $table->string('vessel_material', 100)->default('Stainless Steel');
            $table->decimal('base_reference_temperature', 6, 2)->default(20.00);
            $table->string('calibration_certificate_number', 100)->nullable();
            $table->date('calibration_date')->nullable();
            $table->date('calibration_expiry_date')->nullable();
            $table->timestamps();
        });

        // 5. جدول المواصفات الهندسية للأنبوب العياري (Prover)
        Schema::create('prover_specifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('instrument_id')->unique()->constrained('instruments')->cascadeOnDelete();
            $table->string('type', 50)->default('bidirectional_pipe');
            $table->decimal('inner_diameter', 12, 4)->nullable();
            $table->decimal('wall_thickness', 12, 4)->nullable();
            $table->decimal('nominal_base_volume', 14, 5)->nullable();
            $table->decimal('cubical_expansion_coef', 14, 8)->nullable();
            $table->decimal('elasticity_modulus', 18, 4)->nullable();
            $table->string('material', 100)->default('Mild Steel');
            $table->boolean('pulse_interpolation')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prover_specifications');
        Schema::dropIfExists('standard_gauge_specifications');
        Schema::dropIfExists('flow_computer_transmitter');
        Schema::dropIfExists('instrument_specifications');
        Schema::dropIfExists('instruments');
    }
};
