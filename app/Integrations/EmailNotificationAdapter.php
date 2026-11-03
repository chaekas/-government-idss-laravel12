<?php

namespace App\Integrations;

use App\Contracts\OutboundIntegrationAdapter;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class EmailNotificationAdapter implements OutboundIntegrationAdapter
{
    public function deliver(string $channel, ?string $recipient, ?string $subject, array $payload): array
    {
        if ($channel !== 'email' || !$recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email recipient and email channel are required.');
        }

        $body = (string) ($payload['message'] ?? '');
        if ($body === '') {
            throw new RuntimeException('Email message body is empty.');
        }

        Mail::raw($body, function ($message) use ($recipient, $subject) {
            $message->to($recipient)->subject($subject ?: 'Government IDSS notification');
        });

        return ['external_reference' => null, 'provider' => config('mail.default')];
    }
}
