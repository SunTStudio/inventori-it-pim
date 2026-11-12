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
            $table->string('alias')->nullable()->after('hostname');
            $table->string('department')->nullable()->after('alias');
            $table->string('location')->nullable()->after('department');
            $table->text('notes')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pcs', function (Blueprint $table): void {
            $table->dropColumn(['alias', 'department', 'location', 'notes']);
        });
    }
};
