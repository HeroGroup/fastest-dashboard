<?php

namespace App\Providers;

use FastestModels\User;
use FastestModels\UserDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('api-token', function ($request) {
            if ($request->api_token) {
                return UserDetail::where('api_token', '=', str_replace("\"","",$request->api_token))->first();
            } elseif ($request->header('api-token')){
                return UserDetail::where('api_token', '=', str_replace("\"","",$request->header('api-token')))->first();
            }
        });
    }
}
