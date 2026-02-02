<?php

namespace App\Domain\Webhooks\Services;

use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class StripeWebhookService
{
    /**
     * Store the Stripe webhook event or return the existing one if it's a duplicate.
     */
    public function receiveEvent(string $providerEventId, ?string $eventType, array $payload): WebhookEvent
    {
        try {
            return WebhookEvent::query()->create([
                'uuid' => (string) Str::uuid(),
                'provider' => 'stripe',
                'provider_event_id' => $providerEventId,
                'type' => $eventType,
                'raw_payload' => $payload,
                'status' => 'received',
            ]);
        } catch (QueryException $e) {
            // MySQL duplicate key SQLSTATE = 23000
            $sqlState = $e->errorInfo[0] ?? null;

            if ($sqlState === '23000') {
                /** @var WebhookEvent $existing */
                $existing = WebhookEvent::query()
                    ->where('provider', 'stripe')
                    ->where('provider_event_id', $providerEventId)
                    ->firstOrFail();

                return $existing;
            }

            throw $e;
        }
    }
}
