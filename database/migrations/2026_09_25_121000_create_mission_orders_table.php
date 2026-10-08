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
        Schema::create('mission_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->boolean('is_leader')->default(false)->index();
            $table->string('status', 50)->default('active')->index();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->decimal('daily_rate', 12, 2)->default(0.00);
            $table->string('order_reference', 50)->nullable()->index();
            $table->string('destination', 255)->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->boolean('all_vehicles')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_orders');
    }
};
