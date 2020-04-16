<?php

namespace Armincms\Irmoble;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova; 
use Armincms\Launcher\Launcher;  

class ToolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'irmoble');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');
        $this->app->booted(function () {
            $this->routes();
        }); 
        \Gate::policy(User::class, ProfilePolicy::class); 
        Nova::serving([$this, 'servingNova']);
    }

    public function servingNova()
    { 
        Launcher::resources([
            Residence::class, 
        ]);

        Nova::$cards = collect(Nova::$cards)->filter(function($card) { 
            return get_class($card) !== \Laravel\Nova\Cards\Help::class;
        })->push(new Launcher)->toArray();

        Nova::$tools = collect(Nova::$tools)->map(function($tool) { 
            if(get_class($tool) == \Laravel\Nova\Tools\ResourceManager::class) {
                return new ResourceManager;
            }

            return $tool;
        })->toArray();

        Nova::resources([
            Residence::class,
            Profile::class
        ]);

        Nova::script('irmoble-support', __DIR__.'/../dist/js/tool.js'); 

        Nova::provideToScript([
            'irmoble' => [
                'support' => [
                    'phone'     => '',
                    'mobile'    => '',
                    'fax'       => '',
                ]
            ]
        ]);
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['auth:api'])
                ->prefix('nova-vendor/irmoble')
                ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
