<?php

namespace App\Providers;

use App\Models\Label;
use App\Models\Marker;
use App\Models\ProgressMark;
use App\Policies\LabelPolicy;
use App\Policies\MarkerPolicy;
use App\Policies\ProgressMarkPolicy;
use App\Services\SyncService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SyncService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Marker::class, MarkerPolicy::class);
        Gate::policy(Label::class, LabelPolicy::class);
        Gate::policy(ProgressMark::class, ProgressMarkPolicy::class);
    }
}
