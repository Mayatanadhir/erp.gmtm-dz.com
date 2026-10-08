<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_id')->nullable()->constrained('missions')->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->string('ods', 45)->nullable();
            $table->string('code_ref', 45)->nullable();
            $table->string('type', 45)->nullable();
            $table->string('frequency', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
