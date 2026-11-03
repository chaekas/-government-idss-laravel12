<?php

namespace Tests\Feature;

use App\Contracts\OutboundIntegrationAdapter;
use App\Models\IntegrationLog;
use App\Models\IntegrationOutbox;
use App\Services\IntegrationOutboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeApprovedAdapter implements OutboundIntegrationAdapter
{
    public function deliver(string $channel, ?string $recipient, ?string $subject, array $payload): array
    {
        return ['external_reference' => 'FAKE-DELIVERY-001'];
    }
}

class IntegrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_government_provider_is_blocked_and_logged_without_network_call(): void
    {
        config(['idss_integrations.providers.utambuzi' => ['enabled' => false, 'adapter' => null]]);
        $outbox = app(IntegrationOutboxService::class)->enqueue(
            'utambuzi', 'api', 'case_registered', null, ['case_code' => 'CASE-TEST']
        );

        $processed = app(IntegrationOutboxService::class)->processPending();

        $this->assertSame(0, $processed);
        $this->assertSame('blocked', $outbox->fresh()->status);
        $this->assertDatabaseHas('integration_logs', [
            'provider' => 'utambuzi',
            'operation' => 'case_registered',
            'status' => 'blocked',
        ]);
    }

    public function test_enabled_approved_adapter_processes_outbox_and_records_reference(): void
    {
        config(['idss_integrations.providers.test-provider' => [
            'enabled' => true,
            'adapter' => FakeApprovedAdapter::class,
        ]]);
        $outbox = app(IntegrationOutboxService::class)->enqueue(
            'test-provider', 'email', 'case_alert', 'officer@example.test',
            ['message' => 'Test delivery'], 'IDSS test'
        );

        $processed = app(IntegrationOutboxService::class)->processPending();

        $this->assertSame(1, $processed);
        $this->assertSame('sent', $outbox->fresh()->status);
        $this->assertSame('FAKE-DELIVERY-001', $outbox->fresh()->external_reference);
        $this->assertDatabaseHas('integration_logs', [
            'provider' => 'test-provider',
            'operation' => 'case_alert',
            'status' => 'sent',
            'external_reference' => 'FAKE-DELIVERY-001',
        ]);
    }
}
