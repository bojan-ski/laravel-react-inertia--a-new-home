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
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->text('ein')->nullable()->after('legal_name');
            $table->string('license_number', 50)->nullable()->after('ein');
            $table->string('license_state', 2)->nullable()->after('license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['legal_name', 'ein', 'license_number', 'license_state']);
        });
    }
};
