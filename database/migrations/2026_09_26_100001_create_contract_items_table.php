<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->cascadeOnDelete();
            $table->unsignedBigInteger('item_type_id')->nullable()->comment('FK → item_types.id (constraint added later)');
            $table->string('designation', 200)->nullable();
            $table->unsignedInteger('quantity')->nullable()->comment('Planned total quantity');
            $table->decimal('unit_price', 15, 2)->nullable()->comment('Sale price per unit (DA)');
            $table->string('type', 45)->nullable()->comment('service or supply');
            $table->decimal('unit_cost', 15, 2)->nullable()->comment('Purchase cost per unit (DA)');
            $table->enum('frequency', ['annuelle', 'semestrielle'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
