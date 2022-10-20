<?php

namespace jigarakatidus\HttpToCurl\Tests;

use GuzzleHttp\Psr7\Request as Psr7Request;
use Illuminate\Http\Client\Request;
use jigarakatidus\HttpToCurl\CurlCommandLineGenerator;
use PHPUnit\Framework\TestCase;

class CurlCommandLineGeneratorTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function it_should_return_get_command(): void
    {
        $command = CurlCommandLineGenerator::generate(new Request(new Psr7Request('get', 'https://example.com')));

        $this->assertEquals($command, "curl -H 'Host: example.com' -X 'GET' 'https://example.com'");
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_should_return_get_command_with_parameters(): void
    {
        $command = CurlCommandLineGenerator::generate(new Request(new Psr7Request('get', 'https://example.com?foo=bar')));

        $this->assertEquals($command, "curl -H 'Host: example.com' -X 'GET' 'https://example.com?foo=bar'");
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_should_return_post_command_with_form_body(): void
    {
        $psr7Request = new Psr7Request(
            'post', 
            'https://example.com', 
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ], 
            http_build_query(['foo' => 'bar'])
        );

        $command = CurlCommandLineGenerator::generate(new Request($psr7Request));

        $this->assertEquals(
            $command, 
            "curl -H 'Host: example.com' -H 'Content-Type: application/x-www-form-urlencoded' -d 'foo=bar' -X 'POST' 'https://example.com'"
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_should_return_post_command_with_json_body(): void
    {
        $psr7Request = new Psr7Request(
            'post', 
            'https://example.com', 
            [
                'Content-Type' => 'application/json',
            ], 
            json_encode(['foo' => 'bar'])
        );

        $command = CurlCommandLineGenerator::generate(new Request($psr7Request));

        $this->assertEquals(
            $command, 
            "curl -H 'Host: example.com' -H 'Content-Type: application/json' -d '{\"foo\":\"bar\"}' -X 'POST' 'https://example.com'"
        );
    }
}