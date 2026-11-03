<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_hsts_is_only_added_when_request_is_https_and_policy_is_enabled(): void
    {
        config(['idss.security.hsts_enabled' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->withServerVariables(['HTTPS' => 'on'])
            ->get(route('login'))
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
