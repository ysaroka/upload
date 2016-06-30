<?php

namespace App\Providers;

use App\Components\UserFileStorage;
use App\Interfaces\InterfaceFileStorage;
use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Ratchet\App as RatchetApp;
use WebSocket\Client as WebSocketClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(InterfaceFileStorage::class, function ($app) {
            return $app->make(UserFileStorage::class, ['storagePath' => $app->config->get('storage.userfile.path')]);
        });


        $this->app->singleton(AMQPStreamConnection::class, function ($app) {
            return new AMQPStreamConnection(
                config('amqp.connection.host'),
                config('amqp.connection.port'),
                config('amqp.connection.user'),
                config('amqp.connection.password')
            );
        });

        $this->app->singleton(RatchetApp::class, function ($app) {
            error_reporting(E_ERROR);
            return new RatchetApp(
                config('daemons.wsserver.host'),
                config('daemons.wsserver.port'),
                config('daemons.wsserver.ips')
            );
        });

        $this->app->singleton(WebSocketClient::class, function ($app) {
            return new WebSocketClient('ws://' . config('daemons.wsserver.host') . ':' . config('daemons.wsserver.port') . '/' . config('daemons.wsserver.path'));
        });
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
