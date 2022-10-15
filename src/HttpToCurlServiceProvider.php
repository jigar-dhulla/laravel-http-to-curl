<?php

namespace jigarakatidus\HttpToCurl;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\VarDumper\VarDumper;

class HttpToCurlServiceProvider extends ServiceProvider
{
    public function boot()
    {
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
    }
}
