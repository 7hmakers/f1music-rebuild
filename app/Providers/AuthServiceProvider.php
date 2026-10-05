<?php

namespace App\Providers;

use App\Common\Permission;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('admin', function (User $user) {
            return ($user->permission ?? Permission::User->value) >= Permission::Admin->value;
        });
        Gate::define('censor', function (User $user) {
            return ($user->permission ?? Permission::User->value) >= Permission::Censor->value;
        });
    }
}