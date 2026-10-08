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
        // 1. Update flow_computer_transmitter channel_number to string(50)
        Schema::table('flow_computer_transmitter', function (Blueprint $table): void {
            $table->string('channel_number', 50)->default('Ch1')->change();
        });

        // 2. Add Compact SVP coefficients to prover_specifications
        Schema::table('prover_specifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('prover_specifications', 'area_expansion_coef')) {
                $table->decimal('area_expansion_coef', 14, 8)->nullable()->after('cubical_expansion_coef');
            }
            if (! Schema::hasColumn('prover_specifications', 'linear_expansion_coef')) {
                $table->decimal('linear_expansion_coef', 14, 8)->nullable()->after('area_expansion_coef');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prover_specifications', function (Blueprint $table): void {
            if (Schema::hasColumn('prover_specifications', 'linear_expansion_coef')) {
                $table->dropColumn('linear_expansion_coef');
            }
            if (Schema::hasColumn('prover_specifications', 'area_expansion_coef')) {
                $table->dropColumn('area_expansion_coef');
            }
        });

        Schema::table('flow_computer_transmitter', function (Blueprint $table): void {
            $table->unsignedInteger('channel_number')->default(1)->change();
        });
    }
};
