<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\SavedSearchService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SendSavedSearchDigests extends Command
{
    protected $signature = 'saved-searches:send-digest';

    protected $description = 'Email each user one digest of newly approved listings matching their alert-enabled saved searches';

    public function __construct(
        private readonly SavedSearchService $savedSearchService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $runStartedAt = now();
        $sent = 0;

        User::query()
            ->where('status', UserStatus::Active->value)
            ->where('role', '!=', UserRole::Admin->value)
            ->whereHas('savedSearches', fn (Builder $query) => $query->where('alerts_enabled', true))
            ->with(['agency', 'savedSearches' => fn ($query) => $query->where('alerts_enabled', true)->orderBy('id')])
            ->chunkById(100, function (Collection $users) use ($runStartedAt, &$sent): void {
                foreach ($users as $user) {
                    if ($this->savedSearchService->sendDigest($user, $runStartedAt)) {
                        $sent++;
                    }
                }
            });

        $this->info("Sent {$sent} digests.");

        return self::SUCCESS;
    }
}
