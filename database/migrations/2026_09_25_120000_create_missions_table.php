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
        Schema::create('missions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->string('reference', 100)->unique();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->unsignedSmallInteger('mob_dmob_days')->default(0);
            $table->text('description')->nullable();
            $table->string('status', 50)->default('planned')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
