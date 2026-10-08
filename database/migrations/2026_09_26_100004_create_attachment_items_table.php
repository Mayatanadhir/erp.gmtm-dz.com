<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attachment_id')->nullable()->constrained('attachments')->cascadeOnDelete();
            $table->foreignId('contract_item_id')->nullable()->constrained('contract_items')->cascadeOnDelete();
            $table->decimal('actual_quantity', 10, 2)->nullable();
            $table->decimal('planned_quantity', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_items');
    }
};
