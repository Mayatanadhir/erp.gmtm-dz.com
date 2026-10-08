<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 110)->nullable()->unique();
            $table->string('object', 200)->nullable();
            $table->date('date_signature')->nullable();
            $table->unsignedInteger('duree')->nullable()->comment('Duration in months');
            $table->decimal('montant_global_prevu', 15, 2)->nullable()->comment('Planned global amount in DA');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('garantie_id')->nullable()->constrained('garanties')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
