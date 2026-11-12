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
        Schema::table('pcs', function (Blueprint $table) {
            $table->string('gpu', 150)->nullable()->after('cpu_threads');
            $table->json('gpu_details')->nullable()->after('gpu');
            $table->json('monitors')->nullable()->after('gpu_details');
            $table->json('battery')->nullable()->after('uptime_h');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcs', function (Blueprint $table) {
            $table->dropColumn(['gpu', 'gpu_details', 'monitors', 'battery']);
        });
    }
};
