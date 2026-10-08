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
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'warranty_id')) {
            Schema::table('contracts', function (Blueprint $table): void {
                $table->dropForeign(['warranty_id']);
                $table->renameColumn('warranty_id', 'garantie_id');
                $table->foreign('garantie_id')->references('id')->on('garanties')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'garantie_id')) {
            Schema::table('contracts', function (Blueprint $table): void {
                $table->dropForeign(['garantie_id']);
                $table->renameColumn('garantie_id', 'warranty_id');
                $table->foreign('warranty_id')->references('id')->on('garanties')->nullOnDelete();
            });
        }
    }
};
