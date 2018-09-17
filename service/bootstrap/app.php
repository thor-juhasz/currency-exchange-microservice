<?php

require_once __DIR__.'/../vendor/autoload.php';

date_default_timezone_set('UTC');


try {
    (new Dotenv\Dotenv(__DIR__.'/../', '.env.local'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    try {
        (new Dotenv\Dotenv(__DIR__.'/../'))->load();
    } catch (Dotenv\Exception\InvalidPathException $e) {}
}



/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->withFacades();

// $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

// Register sentry if we're not in debug mode
if (! (bool) env('APP_DEBUG', true)) {
    $app->register('Sentry\SentryLaravel\SentryLumenServiceProvider');
}

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->routeMiddleware([
    'ApiAuth' => App\Http\Middleware\ApiAuth::class
]);

/*
|--------------------------------------------------------------------------
| Get local IP and set consul endpoint
|--------------------------------------------------------------------------
*/
// Consul endpoint from .env file
$consul = env('CONSUL_ENDPOINT');

// If the LOCAL_IP system environment variable is set, use that as the consul endpoint, with default port 8500.
if (getenv('CONSUL_IP')) {
    $consul = 'http://' . trim(getenv('CONSUL_IP')) . ':8500';
}

// If container is being run in AWS, and we're in production mode, get IP of current instance, and use that with default port 8500.
if ((bool) env('CONSUL_AWS') && ! (bool) env('APP_DEBUG')) {
    $ip = trim(file_get_contents('http://169.254.169.254/latest/meta-data/local-ipv4'));
    if ($ip) {
        $consul = 'http://' . $ip . ':8500';
    }
}

// Check only done so it doesn't trigger an exception for defining the constant multiple times.
if ( ! defined('CONSUL_ENDPOINT'))
    // Define our consul endpoint as a PHP constant.
    define('CONSUL_ENDPOINT', $consul);

/*
|--------------------------------------------------------------------------
| Load secret key
|--------------------------------------------------------------------------
 */
$secret_key = env('SHARED_SECRET');

if ( ! defined('API_SECRET'))
    define('API_SECRET', $secret_key);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

require __DIR__.'/../routes/default.php';

$app->group(['namespace' => 'App\Http\Controllers\v1'], function ($app) {
    require __DIR__.'/../routes/api_v1.php';
});

return $app;
