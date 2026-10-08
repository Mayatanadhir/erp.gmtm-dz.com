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
        Schema::table('charges', function (Blueprint $table) {
            $table->index(['type', 'date'], 'charges_type_date_composite_index');
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->index(['status', 'start_date'], 'missions_status_start_date_composite_index');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['event', 'created_at'], 'activity_log_event_created_at_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropIndex('charges_type_date_composite_index');
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->dropIndex('missions_status_start_date_composite_index');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_event_created_at_composite_index');
        });
    }
};
