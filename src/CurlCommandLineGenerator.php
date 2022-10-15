<?php

namespace jigarakatidus\HttpToCurl;

use Illuminate\Http\Client\Request;
use jigarakatidus\CommandLineGenerator;

class CurlCommandLineGenerator
{
    public static function generate(Request $request, array $options): string
    {
        // Set binary
        $commandGenerator = new CommandLineGenerator('curl');

        // Set Headers
        foreach ($request->headers() as $key => $headerValues) {
            foreach ($headerValues as $value) {
                $commandGenerator->addOption('H', sprintf('%s: %s', ucfirst($key), $value));
            }
        }

        if (in_array($request->method(), ['post', 'put', 'patch'])) {
            // Set Data according to Json
            if ($request->isJson()) {
                $data = json_encode($request->data());
            }

            // Set Data according to Form
            if ($request->isForm()) {
                $data = http_build_query($request->data());
            }

            $commandGenerator->addOption('d', $data);

            // TODO Multipart
        }

        $commandGenerator->addOption('X', strtoupper($request->method()));
        $commandGenerator->addArgument($request->url());
        return $commandGenerator->generate();
    }
}
