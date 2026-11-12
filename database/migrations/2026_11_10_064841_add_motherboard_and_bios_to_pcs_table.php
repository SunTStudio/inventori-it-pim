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
            $table->string('motherboard', 150)->nullable()->after('model');
            $table->string('bios_version', 100)->nullable()->after('motherboard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcs', function (Blueprint $table): void {
            $table->dropColumn(['motherboard', 'bios_version']);
        });
    }
};
