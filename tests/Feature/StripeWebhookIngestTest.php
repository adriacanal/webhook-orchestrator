<?php

use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ingests a stripe webhook event', function () {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    config()->set('services.stripe.webhook_tolerance', 300);

    $payload = json_encode([
        'id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => ['amount' => 4999],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $signature = stripeSignatureHeader($payload, 'whsec_test');

    rawStripePost('/api/webhooks/stripe', $payload, [
        'Stripe-Signature' => $signature,
    ])->assertStatus(200);

    expect(WebhookEvent::count())->toBe(1);

    expect(WebhookEvent::first())->toMatchArray([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
    ]);
});

it('is idempotent when receiving duplicate stripe events', function () {
    config()->set('services.stripe.webhook_secret', 'whsec_test');

    $payload = json_encode([
        'id' => 'evt_999',
        'type' => 'payment_intent.succeeded',
    ], JSON_UNESCAPED_SLASHES);

    $signature = stripeSignatureHeader($payload, 'whsec_test');

    rawStripePost('/api/webhooks/stripe', $payload, [
        'Stripe-Signature' => $signature,
    ])->assertStatus(200);

    $second = rawStripePost('/api/webhooks/stripe', $payload, [
        'Stripe-Signature' => $signature,
    ])->assertStatus(200);

    $second
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'idempotent' => true,
        ]);

    expect(WebhookEvent::count())->toBe(1);
});

it('returns 422 when provider event id is missing', function () {
    config()->set('services.stripe.webhook_secret', 'whsec_test');

    $payload = json_encode([
        'type' => 'payment_intent.succeeded',
    ], JSON_UNESCAPED_SLASHES);

    $signature = stripeSignatureHeader($payload, 'whsec_test');

    rawStripePost('/api/webhooks/stripe', $payload, [
        'Stripe-Signature' => $signature,
    ])->assertStatus(422);
});
