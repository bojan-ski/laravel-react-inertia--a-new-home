<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('listings')
            ->where('area_unit', 'sqft')
            ->update([
                'area_value' => DB::raw('ROUND(area_value * 0.092903, 2)'),
                'area_unit' => 'm²',
            ]);

        DB::table('listings')
            ->whereNull('area_unit')
            ->update(['area_unit' => 'm²']);

        DB::table('listings')
            ->where('currency', '<>', 'EUR')
            ->update(['currency' => 'EUR']);
    }

    /**
     * Intentionally a no-op: the sqft → m² conversion rounds and the currency
     * relabel discards the original currency, so neither can be reversed.
     */
    public function down(): void
    {
        //
    }
};
