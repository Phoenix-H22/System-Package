<?php
namespace MainSys\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define the routes provided by your package.
     */
    public function boot(): void
    {
        $this->mapApiRoutes();
    }

    /**
     * Load API routes for the package.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../../routes/api.php');
    }
}
