<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\ContactMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Models\ReportedChatMessage;
use App\Models\SavedSearch;
use App\Models\User;
use App\Policies\AgencyPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\ConversationPolicy;
use App\Policies\ListingPolicy;
use App\Policies\ListingReportPolicy;
use App\Policies\ReportedChatMessagePolicy;
use App\Policies\SavedSearchPolicy;
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
        Gate::policy(ListingReport::class, ListingReportPolicy::class);
        Gate::policy(SavedSearch::class, SavedSearchPolicy::class);

        $this->configureRateLimiters();
    }

    /**
     * Register the named rate limiters guarding the form submissions.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            return [
                Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
                Limit::perMinute(20)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request): array {
            return [
                Limit::perMinute(5)->by('minute:'.$request->ip()),
                Limit::perHour(20)->by('hour:'.$request->ip()),
            ];
        });

        RateLimiter::for('password', function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) $request->ip());
        });

        RateLimiter::for('contact', function (Request $request): array {
            return [
                Limit::perMinute(3)->by('minute:'.$request->ip()),
                Limit::perHour(10)->by('hour:'.$request->ip()),
            ];
        });

        RateLimiter::for('chat', function (Request $request): Limit {
            return Limit::perMinute(20)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('conversation-start', function (Request $request): Limit {
            return Limit::perHour(20)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('listing-images', function (Request $request): Limit {
            return Limit::perMinute(20)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('listing-write', function (Request $request): Limit {
            return Limit::perMinute(10)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('password-confirm', function (Request $request): Limit {
            return Limit::perMinute(5)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('listing-report', function (Request $request): Limit {
            return Limit::perHour(10)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('saved-search', function (Request $request): Limit {
            return Limit::perHour(20)->by($this->rateLimitKey($request));
        });
    }

    /**
     * Key a limit by the signed-in user, falling back to the IP address.
     */
    protected function rateLimitKey(Request $request): string
    {
        return (string) ($request->user()?->id ?: $request->ip());
    }
}
