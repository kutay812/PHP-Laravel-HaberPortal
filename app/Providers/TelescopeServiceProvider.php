<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(static function (IncomingEntry $entry) use ($isLocal): bool {
            return $isLocal
                || $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from ever being logged by Telescope (including local).
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters([
            '_token',
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'new_password_confirmation',
            'token',
        ]);

        Telescope::hideRequestHeaders([
            'cookie',
            'authorization',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Restrict Telescope access strictly to Super Admin accounts.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', static function (User $user): bool {
            return $user->hasRole(RoleName::SuperAdmin->value);
        });
    }
}
