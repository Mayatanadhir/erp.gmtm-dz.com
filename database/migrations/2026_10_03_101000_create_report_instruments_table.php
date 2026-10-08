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
        if (! Schema::hasTable('report_instruments')) {
            Schema::create('report_instruments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_mission_id')->constrained('reports')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnUpdate();
                $table->unsignedInteger('sequence')->default(1)->comment('Order of instrument in report');
                $table->timestamps();

                $table->unique(['report_mission_id', 'instrument_id'], 'uq_report_instrument');
                $table->unique(['report_mission_id', 'sequence'], 'uq_report_instrument_sequence');
                $table->index('report_mission_id', 'idx_report_instruments_report_mission_id');
                $table->index('instrument_id', 'idx_report_instruments_instrument_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_instruments');
    }
};
