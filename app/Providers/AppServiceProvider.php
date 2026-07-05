<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ReportedChatMessage;
use App\Models\User;
use App\Policies\AgencyPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\ListingPolicy;
use App\Policies\ReportedChatMessagePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Listing::class, ListingPolicy::class);
        Gate::policy(Agency::class, AgencyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(ReportedChatMessage::class, ReportedChatMessagePolicy::class);
    }
}
