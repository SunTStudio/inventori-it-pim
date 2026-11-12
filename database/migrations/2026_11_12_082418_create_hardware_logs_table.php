<?php

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
        Schema::create('hardware_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pc_id')->constrained('pcs')->cascadeOnDelete();
            $table->string('component', 50); // RAM, Storage, CPU, GPU, Motherboard
            $table->string('change_type', 30); // upgraded, downgraded, replaced, removed, added
            $table->text('description');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();

            $table->index(['pc_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hardware_logs');
    }
};
