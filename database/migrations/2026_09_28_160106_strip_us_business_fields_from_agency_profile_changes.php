<?php

use App\Models\AgencyProfileChange;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        AgencyProfileChange::query()->each(function (AgencyProfileChange $change): void {
            $remaining = Arr::except($change->proposed_values ?? [], ['ein', 'license_number', 'license_state']);

            if ($remaining === []) {
                $change->delete();

                return;
            }

            if ($remaining !== $change->proposed_values) {
                $change->proposed_values = $remaining;
                $change->saveQuietly();
            }
        });
    }

    /**
     * Intentionally a no-op: the stripped US values were proposals for columns
     * that no longer exist, so there is nothing to restore them into.
     */
    public function down(): void
    {
        //
    }
};
