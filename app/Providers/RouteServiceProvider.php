<?php

namespace App\Providers;

use App\Account;
use App\Currency;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::model('iso', Currency::class);

        Route::bind(
            'account',
            $this->accountBindClosure()
        );
        Route::bind(
            'receiverAccount',
            $this->accountBindClosure()
        );
        Route::bind(
            'senderAccount',
            $this->accountBindClosure()
        );
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    /**
     * @return \Closure
     */
    private function accountBindClosure(): \Closure
    {
        return function ($id) {
            if (! is_numeric($id)) {
                abort(400, 'Invalid account id.');
            }

            $account = Account::whereKey($id)->first();
            if (! $account) {
                abort(404, 'Account not found.');
            }

            return $account;
        };
    }
}
