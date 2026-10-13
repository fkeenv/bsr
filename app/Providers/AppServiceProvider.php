<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

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
        $this->configureDefaults();
        $this->configureRateLimiting();
        Route::bind('role', fn (string $value, RoutingRoute $route): Role => Role::query()
            ->where('guard_name', 'web')->where($route->bindingFieldFor('role') ?? 'id', $value)->firstOrFail());
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('property-invitation-lookup', fn (Request $request): array => [
            Limit::perMinute(15)->by('account:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(30)->by('network:'.$request->ip()),
        ]);

        RateLimiter::for('property-invitation-redemption', fn (Request $request): array => [
            Limit::perMinute(5)->by('account:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(10)->by('network:'.$request->ip()),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
