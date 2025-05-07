<?php

namespace jigarakatidus\HttpToCurl;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\Client\Events\RequestSending;

class HttpToCurlServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/laravel-http-to-curl.php' => config_path('http-to-curl.php'),
        ], 'http-to-curl-config');

        PendingRequest::macro('ddWithCurl', function () {
            /** @var PendingRequest $this */
            $values = func_get_args();

            return self::beforeSending(function (Request $request, array $options) use ($values) {
                foreach (array_merge($values, [CurlCommandLineGenerator::generate($request, $options)]) as $value) {
                    VarDumper::dump($value);
                }

                exit(1);
            });
        });

        if (config("http-to-curl.logging.enabled")) {
            Event::listen(RequestSending::class, function (RequestSending $event) {
                $request = $event->request;
                $curlCommand = CurlCommandLineGenerator::generate($request);

                Log::channel(config("http-to-curl.logging.channel"))
                    ->log(config("http-to-curl.logging.log_level"), $curlCommand);
            });
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-http-to-curl.php', 'http-to-curl'
        );
    }
}
