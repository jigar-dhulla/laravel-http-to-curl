<?php

namespace jigarakatidus\HttpToCurl\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use jigarakatidus\HttpToCurl\HttpToCurlServiceProvider;
use jigarakatidus\HttpToCurl\CurlCommandLineGenerator;
use Symfony\Component\VarDumper\VarDumper;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test-specific service provider that overrides the ddWithCurl macro
 * to avoid actually calling exit() during tests
 */
class TestHttpToCurlServiceProvider extends HttpToCurlServiceProvider
{
    protected $dumpedValues = [];
    protected $exitCalled = false;
    protected $exitStatus = null;

    /**
     * Reset all captured data
     */
    public function resetCaptures()
    {
        $this->dumpedValues = [];
        $this->exitCalled = false;
        $this->exitStatus = null;
    }

    /**
     * Get all the values that were dumped
     */
    public function getDumpedValues()
    {
        return $this->dumpedValues;
    }

    /**
     * Check if exit was called
     */
    public function wasExitCalled()
    {
        return $this->exitCalled;
    }

    /**
     * Get the exit status that would have been used
     */
    public function getExitStatus()
    {
        return $this->exitStatus;
    }

    /**
     * Boot the service provider
     */
    public function boot()
    {
        // Call parent register to setup configs
        parent::register();

        // Skip parent boot to avoid registering the original macro
        $this->publishes([
            __DIR__ . '/../config/laravel-http-to-curl.php' => config_path('http-to-curl.php'),
        ], 'http-to-curl-config');

        // Register our test version of the macro
        PendingRequest::macro('ddWithCurl', function () {
            /** @var PendingRequest $this */
            $values = func_get_args();
            $testProvider = app(TestHttpToCurlServiceProvider::class);

            return $this->beforeSending(function (Request $request, array $options) use ($values, $testProvider) {
                // Capture the values that would have been dumped
                foreach (array_merge($values, [CurlCommandLineGenerator::generate($request, $options)]) as $value) {
                    // Instead of VarDumper::dump($value), store it
                    $testProvider->captureValue($value);
                }
                
                // Record that exit was called, but don't actually exit
                $testProvider->captureExit(1);
                
                // Return a response so the test can continue
                return Http::response('exit-prevented', 200);
            });
        });
        
        // Register event listeners if needed
        if (config("http-to-curl.logging.enabled")) {
            $this->registerLoggingListeners();
        }
    }

    /**
     * Register the logging event listener
     */
    protected function registerLoggingListeners()
    {
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Http\Client\Events\RequestSending::class, function (\Illuminate\Http\Client\Events\RequestSending $event) {
            $request = $event->request;
            $curlCommand = CurlCommandLineGenerator::generate($request);

            \Illuminate\Support\Facades\Log::channel(config("http-to-curl.logging.channel"))
                ->log(config("http-to-curl.logging.log_level"), $curlCommand);
        });
    }

    /**
     * Capture a value that would have been dumped
     */
    public function captureValue($value)
    {
        $this->dumpedValues[] = $value;
    }

    /**
     * Capture exit call
     */
    public function captureExit($status)
    {
        $this->exitCalled = true;
        $this->exitStatus = $status;
    }
}

class DdWithCurlTest extends TestCase
{
    /**
     * @var TestHttpToCurlServiceProvider
     */
    protected $testProvider;

    /**
     * Define environment setup
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app->singleton(TestHttpToCurlServiceProvider::class, function ($app) {
            return new TestHttpToCurlServiceProvider($app);
        });
    }

    protected function getPackageProviders($app)
    {
        // Register our test provider instead of the real one
        return [TestHttpToCurlServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Get our test provider
        $this->testProvider = $this->app->make(TestHttpToCurlServiceProvider::class);
        $this->testProvider->resetCaptures();
        
        // Prevent real HTTP requests during tests
        Http::fake([
            '*' => Http::response('', 200),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_should_dump_curl_command_for_get_request()
    {
        // Act - Create a PendingRequest without executing it
        $pendingRequest = Http::withOptions([]);
        $pendingRequest->ddWithCurl()->get('https://example.com');
        
        // Assert
        $this->assertTrue($this->testProvider->wasExitCalled(), "Exit should have been called");
        $this->assertEquals(1, $this->testProvider->getExitStatus(), "Exit should be called with status 1");
        $this->assertNotEmpty($this->testProvider->getDumpedValues(), "Values should have been dumped");
        
        $dumpedValues = $this->testProvider->getDumpedValues();
        $curlCommand = end($dumpedValues);
        $this->assertStringContainsString("curl", $curlCommand);
        $this->assertStringContainsString("-X 'GET'", $curlCommand);
        $this->assertStringContainsString("'https://example.com'", $curlCommand);
    }
    
    #[Test]
    public function it_should_dump_multiple_values_before_curl_command()
    {
        // Arrange
        $testValue1 = "Debug Value 1";
        $testValue2 = ["key" => "Debug Value 2"];
        
        // Act
        $pendingRequest = Http::withOptions([]);
        $pendingRequest->ddWithCurl($testValue1, $testValue2)->get('https://example.com');
        
        // Assert
        $this->assertTrue($this->testProvider->wasExitCalled(), "Exit should have been called");
        $this->assertEquals(1, $this->testProvider->getExitStatus(), "Exit should be called with status 1");
        
        $dumpedValues = $this->testProvider->getDumpedValues();
        $this->assertCount(3, $dumpedValues, "Three values should have been dumped");
        
        $this->assertEquals($testValue1, $dumpedValues[0]);
        $this->assertEquals($testValue2, $dumpedValues[1]);
        
        $curlCommand = $dumpedValues[2];
        $this->assertStringContainsString("curl", $curlCommand);
        $this->assertStringContainsString("-X 'GET'", $curlCommand);
        $this->assertStringContainsString("'https://example.com'", $curlCommand);
    }
    
    #[Test]
    public function it_should_dump_curl_command_for_post_request_with_json()
    {
        // Arrange
        $postData = ["name" => "test", "value" => 123];
        
        // Act
        $pendingRequest = Http::asJson()->withOptions([]);
        $pendingRequest->ddWithCurl()->post('https://example.com', $postData);
        
        // Assert
        $this->assertTrue($this->testProvider->wasExitCalled(), "Exit should have been called");
        $this->assertEquals(1, $this->testProvider->getExitStatus(), "Exit should be called with status 1");
        $this->assertNotEmpty($this->testProvider->getDumpedValues(), "Values should have been dumped");
        
        $dumpedValues = $this->testProvider->getDumpedValues();
        $curlCommand = end($dumpedValues);
        $this->assertStringContainsString("curl", $curlCommand);
        $this->assertStringContainsString("-X 'POST'", $curlCommand);
        $this->assertStringContainsString("'https://example.com'", $curlCommand);
        $this->assertStringContainsString("-d '{\"name\":\"test\",\"value\":123}'", $curlCommand);
    }
    
    #[Test]
    public function it_should_dump_curl_command_for_put_request()
    {
        // Arrange
        $putData = ["updated" => true];
        
        // Act
        $pendingRequest = Http::asJson()->withOptions([]);
        $pendingRequest->ddWithCurl()->put('https://example.com/resource/1', $putData);
        
        // Assert
        $this->assertTrue($this->testProvider->wasExitCalled(), "Exit should have been called");
        $this->assertEquals(1, $this->testProvider->getExitStatus(), "Exit should be called with status 1");
        $this->assertNotEmpty($this->testProvider->getDumpedValues(), "Values should have been dumped");
        
        $dumpedValues = $this->testProvider->getDumpedValues();
        $curlCommand = end($dumpedValues);
        $this->assertStringContainsString("curl", $curlCommand);
        $this->assertStringContainsString("-X 'PUT'", $curlCommand);
        $this->assertStringContainsString("'https://example.com/resource/1'", $curlCommand);
        $this->assertStringContainsString("-d '{\"updated\":true}'", $curlCommand);
    }
    
    #[Test]
    public function it_should_dump_curl_command_with_headers()
    {
        // Act
        $pendingRequest = Http::withHeaders([
            'Authorization' => 'Bearer token123',
            'X-Custom-Header' => 'CustomValue'
        ])->withOptions([]);
        
        $pendingRequest->ddWithCurl()->get('https://example.com');
        
        // Assert
        $this->assertTrue($this->testProvider->wasExitCalled(), "Exit should have been called");
        $this->assertEquals(1, $this->testProvider->getExitStatus(), "Exit should be called with status 1");
        $this->assertNotEmpty($this->testProvider->getDumpedValues(), "Values should have been dumped");
        
        $dumpedValues = $this->testProvider->getDumpedValues();
        $curlCommand = end($dumpedValues);
        $this->assertStringContainsString("curl", $curlCommand);
        $this->assertStringContainsString("-H 'Authorization: Bearer token123'", $curlCommand);
        $this->assertStringContainsString("-H 'X-Custom-Header: CustomValue'", $curlCommand);
        $this->assertStringContainsString("-X 'GET'", $curlCommand);
        $this->assertStringContainsString("'https://example.com'", $curlCommand);
    }
}

