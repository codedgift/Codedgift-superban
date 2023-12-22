<?php

namespace Edenlife\Superban\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Edenlife\Superban\Http\Middleware\SuperbanMiddleware;

class SuperbanMiddlewareTest extends BaseTestCase
{
    protected SuperbanMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SuperbanMiddleware();

        // Mock facades
        Cache::spy();
        RateLimiter::spy();
    }

    protected function tearDown(): void
    {
        // Reset mocked facades
        Cache::flush();
        RateLimiter::flush();

        parent::tearDown();
    }

    /** @test */
    public function it_bans_users_after_too_many_attempts()
    {
        // Simulate environment
        RateLimiter::shouldReceive('tooManyAttempts')
                   ->once()
                   ->withArgs(function ($key, $maxAttempts) {
                       return $key && $maxAttempts === 100;
                   })
                   ->andReturn(true);

        // Mock a request
        $request = Request::create('/test', 'GET');

        // Handle the request through middleware
        $response = $this->middleware->handle($request, function () {}, 100, 1, 10);

        // Assert the response status is 429 (Too Many Requests)
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('You are banned', $response->getContent());
    }

    /** @test */
    public function it_resolves_request_signature_correctly()
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('User-Agent', 'test-agent');

        $key = $this->invokeMethod($this->middleware, 'resolveRequestSignature', [$request]);

        $this->assertIsString($key);
        $this->assertEquals(sha1('127.0.0.1|test-agent|guest_id|guest_email'), $key);
    }

    /** @test */
    public function it_checks_if_user_is_banned_correctly()
    {
        Cache::shouldReceive('get')
             ->once()
             ->withArgs(function ($key) {
                 return str_starts_with($key, '127.0.0.1|test-agent|guest_id|guest_email:banned');
             })
             ->andReturn(true);

        $banned = $this->invokeMethod($this->middleware, 'isBanned', ['127.0.0.1|test-agent|guest_id|guest_email']);

        $this->assertTrue($banned);
    }

    /** @test */
    public function it_bans_user_correctly()
    {
        Cache::shouldReceive('put')
             ->once()
             ->withArgs(function ($key, $value, $minutes) {
                 return str_starts_with($key, '127.0.0.1|test-agent|guest_id|guest_email:banned') && $value === true && $minutes;
             });

        $this->invokeMethod($this->middleware, 'ban', ['127.0.0.1|test-agent|guest_id|guest_email', 10]);
    }

    /** @test */
    public function it_gets_cache_store_correctly()
    {
        $store = $this->invokeMethod($this->middleware, 'getCacheStore');

        $this->assertNotNull($store);
    }

    /**
     * Invokes a private or protected method of a class.
     *
     * @param object $object
     * @param string $methodName
     * @param array  $parameters
     *
     * @return mixed
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = []) : mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
