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
        Schema::create('pcs', function (Blueprint $table): void {
            $table->id();
            $table->string('uid')->unique();
            $table->string('hostname')->index();
            $table->string('ip')->nullable();
            $table->string('mac')->nullable();
            $table->string('login_user')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->string('os')->nullable();
            $table->string('cpu')->nullable();
            $table->unsignedSmallInteger('cpu_cores')->nullable();
            $table->unsignedSmallInteger('cpu_threads')->nullable();
            $table->decimal('ram_gb', 6, 1)->nullable();
            $table->decimal('uptime_h', 8, 1)->nullable();
            $table->json('disks')->nullable();
            $table->timestamp('last_report_at')->nullable();
            $table->timestamp('last_poll_at')->nullable();
            $table->timestamp('sync_requested_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcs');
    }
};
