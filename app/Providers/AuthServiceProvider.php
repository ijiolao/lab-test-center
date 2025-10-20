<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Order::class => \App\Policies\OrderPolicy::class,
        \App\Models\Result::class => \App\Policies\ResultPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define custom gates if needed
        Gate::define('manage-lab-partners', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('collect-specimens', function ($user) {
            return $user->canCollectSpecimens();
        });

        Gate::define('review-results', function ($user) {
            return $user->canReviewResults();
        });

        Gate::define('view-admin-dashboard', function ($user) {
            return $user->isAdmin();
        });
    }
}