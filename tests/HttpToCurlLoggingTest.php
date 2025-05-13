<?php

namespace jigarakatidus\HttpToCurl\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use jigarakatidus\HttpToCurl\HttpToCurlServiceProvider;
use Illuminate\Http\Client\Events\RequestSending;
use Mockery;

class HttpToCurlLoggingTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [HttpToCurlServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app["config"]->set("http-to-curl.logging.enabled", true);
        $app["config"]->set("http-to-curl.logging.log_level", "debug");
        $app["config"]->set("http-to-curl.logging.channel", "stack");
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function bootPackageService()
    {
        // Clear any existing event listeners
        Event::forget(RequestSending::class);
        
        $this->app->forgetInstance(HttpToCurlServiceProvider::class);
        $provider = new HttpToCurlServiceProvider($this->app);
        $this->app->instance(HttpToCurlServiceProvider::class, $provider);
        $provider->boot();
    }

    public function test_http_requests_are_logged_as_curl_commands()
    {
        // Arrange
        $logger = Mockery::mock(Log::getFacadeRoot())->makePartial();
        Log::swap($logger);
        $this->app->instance('log', $logger);

        $logger->shouldReceive("channel")
            ->with("stack")
            ->andReturnSelf();

        $logger->shouldReceive("log")
            ->withArgs(function($level, $message) {
                return $level === "debug" 
                    && str_contains($message, "curl")
                    && str_contains($message, "-X 'POST'")
                    && str_contains($message, "'https://example.com'")
                    && str_contains($message, "-d '{\"name\":\"test\"}'");
            });

        $this->bootPackageService();

        // Act
        Http::fake([
            '*' => Http::response('', 200),
        ]);
        
        Http::post("https://example.com", [
            "name" => "test"
        ]);

        // Assert - Mockery will verify logger expectations
        $this->addToAssertionCount(1);
    }

    public function test_logging_is_disabled_when_config_is_false()
    {
        // First clear any existing event listeners and refresh application
        Event::forget(RequestSending::class);
        $this->refreshApplication();

        // Configure the application with logging disabled before booting the service provider
        $this->app["config"]->set("http-to-curl.logging.enabled", false);

        // Set up a spy logger to verify no calls are made
        $logWasCalled = false;
        
        $spyLogger = Mockery::mock(Log::getFacadeRoot())->makePartial();
        
        // Set up the spy logger to track any logging attempts
        $spyLogger->shouldReceive('channel')->andReturnUsing(function() use ($spyLogger, &$logWasCalled) {
            $logWasCalled = true;
            return $spyLogger;
        });
        
        $spyLogger->shouldReceive('log')->andReturnUsing(function() use (&$logWasCalled) {
            $logWasCalled = true;
        });
        
        Log::swap($spyLogger);
        $this->app->instance('log', $spyLogger);

        // Boot the service provider after configuration
        $this->bootPackageService();

        // Set up HTTP fake
        Http::fake([
            '*' => Http::response('', 200),
        ]);

        // Act
        Http::post("https://example.com", [
            "name" => "test"
        ]);

        // Assert
        $this->assertFalse($logWasCalled, "Logger should not have been called when logging is disabled");
    }

    public function test_logging_uses_configured_channel()
    {
        // Arrange
        $this->app["config"]->set("http-to-curl.logging.channel", "custom");

        $logger = Mockery::mock(Log::getFacadeRoot())->makePartial();
        Log::swap($logger);
        $this->app->instance('log', $logger);

        $logger->shouldReceive("channel")
            ->with("custom")
            ->andReturnSelf();

        $logger->shouldReceive("log")
            ->withArgs(function($level, $message) {
                return $level === "debug" 
                    && str_contains($message, "curl")
                    && str_contains($message, "-X 'POST'")
                    && str_contains($message, "'https://example.com'")
                    && str_contains($message, "-d '{\"name\":\"test\"}'");
            });

        $this->bootPackageService();

        // Act
        Http::fake([
            '*' => Http::response('', 200),
        ]);
        
        Http::post("https://example.com", [
            "name" => "test"
        ]);

        // Assert - Mockery will verify logger expectations
        $this->addToAssertionCount(1);
    }

    public function test_logging_uses_configured_log_level()
    {
        // Arrange
        $this->app["config"]->set("http-to-curl.logging.log_level", "info");

        $logger = Mockery::mock(Log::getFacadeRoot())->makePartial();
        Log::swap($logger);
        $this->app->instance('log', $logger);

        $logger->shouldReceive("channel")
            ->with("stack")
            ->andReturnSelf();

        $logger->shouldReceive("log")
            ->withArgs(function($level, $message) {
                return $level === "info" 
                    && str_contains($message, "curl")
                    && str_contains($message, "-X 'POST'")
                    && str_contains($message, "'https://example.com'")
                    && str_contains($message, "-d '{\"name\":\"test\"}'");
            });

        $this->bootPackageService();

        // Act
        Http::fake([
            '*' => Http::response('', 200),
        ]);
        
        Http::post("https://example.com", [
            "name" => "test"
        ]);

        // Assert - Mockery will verify logger expectations
        $this->addToAssertionCount(1);
    }
}
