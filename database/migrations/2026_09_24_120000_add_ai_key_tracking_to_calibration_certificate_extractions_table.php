<?php

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
        if (! Schema::hasColumn('calibration_certificate_extractions', 'ai_key_index')) {
            Schema::table('calibration_certificate_extractions', function (Blueprint $table): void {
                $table->unsignedTinyInteger('ai_key_index')->nullable()->after('ai_model');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('calibration_certificate_extractions', 'ai_key_index')) {
            Schema::table('calibration_certificate_extractions', function (Blueprint $table): void {
                $table->dropColumn('ai_key_index');
            });
        }
    }
};
