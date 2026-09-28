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
            ->where('status', 'approved')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()->addDays(90)]);
    }

    /**
     * Intentionally a no-op: the column is dropped by the schema migration's
     * rollback, and a backfilled value can't be told apart from a real one.
     */
    public function down(): void
    {
        //
    }
};
