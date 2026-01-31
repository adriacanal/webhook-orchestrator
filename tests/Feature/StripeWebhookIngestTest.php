<?php

use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ingests a stripe webhook event', function () {
    $payload = [
        'id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => ['amount' => 4999],
        ],
    ];

    $response = $this->postJson('/api/webhooks/stripe', $payload);

    $response
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(WebhookEvent::count())->toBe(1);

    expect(WebhookEvent::first())->toMatchArray([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
    ]);
});

it('is idempotent when receiving duplicate stripe events', function () {
    $payload = [
        'id' => 'evt_999',
        'type' => 'payment_intent.succeeded',
    ];

    $this->postJson('/api/webhooks/stripe', $payload)->assertOk();

    $second = $this->postJson('/api/webhooks/stripe', $payload);

    $second
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'idempotent' => true,
        ]);

    expect(WebhookEvent::count())->toBe(1);
});

it('returns 422 when provider event id is missing', function () {
    $payload = [
        'type' => 'payment_intent.succeeded',
    ];

    $this->postJson('/api/webhooks/stripe', $payload)
        ->assertStatus(422)
        ->assertJson([
            'ok' => false,
        ]);
});
