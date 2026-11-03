<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_endpoint_checks_database_without_exposing_internal_details(): void
    {
        $this->getJson(route('health.idss'))
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'service' => 'government-idss',
                'checks' => ['database' => 'ok'],
            ]);
    }

    public function test_baseline_security_headers_are_added_to_web_responses(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_hsts_is_only_added_to_secure_requests_when_policy_is_enabled(): void
    {
        config(['idss.security.hsts_enabled' => true]);
        $middleware = app(SecurityHeaders::class);

        $httpRequest = Request::create('http://idss.test/login');
        $httpResponse = $middleware->handle($httpRequest, fn () => response('ok'));
        $this->assertFalse($httpResponse->headers->has('Strict-Transport-Security'));

        $httpsRequest = Request::create('https://idss.test/login');
        $httpsResponse = $middleware->handle($httpsRequest, fn () => response('ok'));
        $this->assertSame('max-age=31536000', $httpsResponse->headers->get('Strict-Transport-Security'));
    }
}
