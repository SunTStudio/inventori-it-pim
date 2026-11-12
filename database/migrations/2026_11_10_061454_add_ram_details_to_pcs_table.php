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
        Schema::table('pcs', function (Blueprint $table): void {
            $table->string('ram_type', 20)->nullable()->after('ram_gb');
            $table->unsignedInteger('ram_speed')->nullable()->after('ram_type');
            $table->json('ram_details')->nullable()->after('ram_speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcs', function (Blueprint $table): void {
            $table->dropColumn(['ram_type', 'ram_speed', 'ram_details']);
        });
    }
};
