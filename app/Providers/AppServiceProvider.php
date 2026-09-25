<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\ContactMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ReportedChatMessage;
use App\Models\User;
use App\Policies\AgencyPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\ConversationPolicy;
use App\Policies\ListingPolicy;
use App\Policies\ReportedChatMessagePolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);

        $this->configureRateLimiters();
    }

    /**
     * Register the named rate limiters guarding the authentication endpoints.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) $request->ip());
        });

        RateLimiter::for('password', function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) $request->ip());
        });

        RateLimiter::for('contact', function (Request $request): Limit {
            return Limit::perMinute(3)->by((string) $request->ip());
        });
    }
}
