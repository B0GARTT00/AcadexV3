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
        // Example if you add policies later
        // \App\Models\Model::class => \App\Policies\ModelPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define custom Gates based on role
        Gate::define('admin', fn($user) => $user->role === 3);
        Gate::define('chairperson', fn($user) => $user->role === 1);
        Gate::define('dean', fn($user) => $user->role === 2);
        Gate::define('instructor', fn($user) => $user->role === 0);
        Gate::define('gecoordinator', fn($user) => $user->role === 4);
        Gate::define('vpaa', fn($user) => $user->role === 5);
        Gate::define('admin-chair', function ($user) {
            return in_array($user->role, [1, 2]); // 1 = Admin, 2 = Chairperson
        });
        
    }
}
