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
            $table->dropColumn(['ein', 'license_number', 'license_state']);
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->string('vat_id', 15)->nullable()->after('legal_name');
            $table->string('registration_number', 50)->nullable()->after('vat_id');
            $table->string('registration_country', 2)->nullable()->after('registration_number');
            $table->string('broker_register_number', 50)->nullable()->after('registration_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['vat_id', 'registration_number', 'registration_country', 'broker_register_number']);
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->text('ein')->nullable()->after('legal_name');
            $table->string('license_number', 50)->nullable()->after('ein');
            $table->string('license_state', 2)->nullable()->after('license_number');
        });
    }
};
