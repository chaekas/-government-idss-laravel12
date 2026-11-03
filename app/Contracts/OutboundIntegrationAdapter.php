<?php

namespace App\Contracts;

interface OutboundIntegrationAdapter
{
    /**
     * Deliver one approved outbound event. Return provider metadata such as an
     * external message/reference ID. Implementations must follow the provider's
     * officially approved API contract.
     */
    public function deliver(string $channel, ?string $recipient, ?string $subject, array $payload): array;
}
