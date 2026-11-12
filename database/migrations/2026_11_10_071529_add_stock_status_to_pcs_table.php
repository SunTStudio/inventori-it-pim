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
            $table->string('stock_status', 20)->default('digunakan')->after('location')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcs', function (Blueprint $table) {
            $table->dropColumn('stock_status');
        });
    }
};
