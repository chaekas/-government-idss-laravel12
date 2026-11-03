<?php

namespace App\Services;

use App\Contracts\OutboundIntegrationAdapter;
use App\Models\IntegrationLog;
use App\Models\IntegrationOutbox;
use Illuminate\Support\Facades\Log;

class IntegrationOutboxService
{
    public function enqueue(string $provider, string $channel, string $eventType, ?string $recipient, array $payload, ?string $subject = null): IntegrationOutbox
    {
        return IntegrationOutbox::create([
            'provider' => $provider,
            'channel' => $channel,
            'event_type' => $eventType,
            'recipient' => $recipient,
            'subject' => $subject,
            'payload' => $payload,
            'status' => 'queued',
            'attempts' => 0,
            'max_attempts' => 5,
            'available_at' => now(),
        ]);
    }

    public function processPending(int $limit = 25): int
    {
        $processed = 0;
        $items = IntegrationOutbox::whereIn('status', ['queued','failed'])
            ->where('available_at', '<=', now())
            ->whereColumn('attempts', '<', 'max_attempts')
            ->orderBy('available_at')
            ->limit($limit)
            ->get();

        foreach ($items as $item) {
            $providerConfig = config('idss_integrations.providers.'.$item->provider, []);
            $adapterClass = $providerConfig['adapter'] ?? null;
            if (!($providerConfig['enabled'] ?? false)) {
                $item->update(['status' => 'blocked', 'last_error' => 'Provider delivery is disabled until the Ministry approves and configures the connector.']);
                $this->log($item, 'blocked', null, 'Provider delivery is disabled.');
                continue;
            }
            if (!$adapterClass || !class_exists($adapterClass)) {
                $item->update(['status' => 'blocked', 'last_error' => 'No approved adapter is configured for this provider.']);
                $this->log($item, 'blocked', null, 'No approved adapter is configured.');
                continue;
            }

            try {
                $adapter = app($adapterClass);
                if (!$adapter instanceof OutboundIntegrationAdapter) {
                    throw new \RuntimeException('Configured adapter must implement OutboundIntegrationAdapter.');
                }

                $item->update(['status' => 'processing', 'attempts' => $item->attempts + 1]);
                $response = $adapter->deliver($item->channel, $item->recipient, $item->subject, $item->payload ?? []);
                $item->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'external_reference' => $response['external_reference'] ?? null,
                    'last_error' => null,
                ]);
                $this->log($item, 'sent', $response, null);
                $processed++;
            } catch (\Throwable $exception) {
                $attempts = $item->fresh()->attempts;
                $failed = $attempts >= $item->max_attempts;
                $item->update([
                    'status' => $failed ? 'failed' : 'queued',
                    'available_at' => now()->addMinutes(min(60, 2 ** max(1, $attempts))),
                    'last_error' => mb_substr($exception->getMessage(), 0, 4000),
                ]);
                $this->log($item, 'failed', null, $exception->getMessage());
                Log::warning('IDSS integration delivery failed', ['outbox_id' => $item->id, 'provider' => $item->provider, 'attempt' => $attempts]);
            }
        }

        return $processed;
    }

    private function log(IntegrationOutbox $item, string $status, ?array $response, ?string $error): void
    {
        IntegrationLog::create([
            'provider' => $item->provider,
            'operation' => $item->event_type,
            'status' => $status,
            'external_reference' => $item->external_reference,
            'request_metadata' => ['channel' => $item->channel, 'event_type' => $item->event_type],
            'response_metadata' => $response,
            'error_message' => $error,
            'occurred_at' => now(),
        ]);
    }
}
