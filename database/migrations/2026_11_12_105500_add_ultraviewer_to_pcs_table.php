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
            $table->string('ultraviewer_id', 50)->nullable()->after('ip');
            $table->string('ultraviewer_pass', 100)->nullable()->after('ultraviewer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcs', function (Blueprint $table): void {
            $table->dropColumn(['ultraviewer_id', 'ultraviewer_pass']);
        });
    }
};

