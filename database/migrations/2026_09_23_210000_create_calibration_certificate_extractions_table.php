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
        Schema::create('calibration_certificate_extractions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users', indexName: 'cce_user_fk')->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment', indexName: 'cce_equip_fk')->nullOnDelete();
            $table->string('file_path');
            $table->char('file_hash', 64)->index();
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('status', 30)->default('pending')->index();
            $table->string('ai_model', 60);
            $table->unsignedTinyInteger('ai_key_index')->nullable();
            $table->json('extracted_data')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('is_applied')->default(false)->index();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_certificate_id')->nullable()->constrained('calibration_certificates', indexName: 'cce_cert_fk')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calibration_certificate_extractions');
    }
};
