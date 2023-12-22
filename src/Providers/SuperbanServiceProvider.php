<?php

namespace Edenlife\Superban\Providers;

use Edenlife\Superban\Facades\Superban as SuperbanFacade;
use Edenlife\Superban\Http\Middleware\SuperbanMiddleware;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\AliasLoader;


class SuperbanServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishConfigs();

        $this->createResponseMacros();
        $this->defineRateLimiters();

        app('router')->aliasMiddleware('superban', SuperbanMiddleware::class);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/superban.php', 'superban'
        );
    }

    /**
     * Create response macros
     *
     * @return void
     */
    protected function createResponseMacros(): void
    {
        Response::macro('success', function (string $message, $data = null) {
            $value = [
                'isError' => false,
                'message' => $message,
                'result' => $data
            ];
            return Response::make($value);
        });

        Response::macro('error', function (string $message, $errors = null, $status = 200) {
            $value = [
                'isError' => true,
                'message' => $message,
                'result' => $errors
            ];
            return Response::make($value, $status);
        });
    }

    /**
     * Define rate limiters.
     *
     * @return void
     */
    protected function defineRateLimiters(): void
    {
        RateLimiter::for('superban', function ($request) {
            $user = $request->user();
            $userId = $user ? $user->id : 'guest';
            $userEmail = $user ? $user->email : 'guest_email';
            $ipAddress = $request->ip();

            // Construct a unique key using available identifiers
            $key = implode('|', [$userId, $userEmail, $ipAddress]);

            // You can adjust these settings or make them configurable
            return Limit::perMinute(1000)->by($key);
        });
    }

    /**
     * Publish configuration file.
     *
     * @return void
     */
    protected function publishConfigs() : void
    {
        $this->registerFacades();

        $this->publishes([
            $this->getConfigsPath() => config_path('superban.php'),
        ], 'superban');
    }

    /**
     * Get local package configuration path.
     *
     * @return string
     */
    private function getConfigsPath() : string
    {
        return __DIR__.'/../config/superban.php';
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerFacades() : void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('superban', SuperbanFacade::class);
    }
}
