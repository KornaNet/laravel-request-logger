<?php

namespace Bilfeldt\RequestLogger;

use Bilfeldt\RequestLogger\Commands\PruneRequestLogsCommand;
use Bilfeldt\RequestLogger\Middleware\LogRequestMiddleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class RequestLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/request-logger.php', 'request-logger');

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/request-logger.php' => config_path('request-logger.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations')
            ], 'migrations');

            $this->commands([
                PruneRequestLogsCommand::class,
            ]);
        }

        $this->registerMiddlewareAlias();
        $this->bootMacros();

        // TODO: Register command PruneRequestLogsCommand::class);
        // TODO: Register EventServiceProvider::class
    }

    private function registerMiddlewareAlias(): void
    {
        $this->app
            ->make(Router::class)
            ->aliasMiddleware('requestlog', LogRequestMiddleware::class);
    }

    private function bootMacros(): void
    {
        Request::macro('enableLog', function (string ...$drivers): Request {
            $loggers = $this->attributes->get('log', []);

            if (empty($drivers)) {
                $loggers[] = RequestLoggerFacade::getDefaultDriver();
            }

            foreach ($drivers as $driver) {
                $loggers[] = $driver;
            }

            $this->attributes->set('log', $loggers);

            return $this;
        });
    }
}
