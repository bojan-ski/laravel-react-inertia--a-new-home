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
        DB::table('conversations')
            ->whereNull('listing_title')
            ->whereNotNull('listing_id')
            ->update([
                'listing_title' => DB::raw('(select title from listings where listings.id = conversations.listing_id)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
