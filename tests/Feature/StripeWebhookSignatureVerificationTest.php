<?php

beforeEach(function () {
    config()->set('services.stripe.webhook_secret', 'whsec_test');
    config()->set('services.stripe.webhook_tolerance', 300);

    $this->payload = json_encode([
        'id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_123',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);
});

it('accepts a webhook when signature is valid', function () {
    $header = stripeSignatureHeader($this->payload, 'whsec_test');

    rawStripePost('/api/webhooks/stripe', $this->payload, [
        'Stripe-Signature' => $header,
    ])->assertStatus(200);
});

it('returns 400 when Stripe-Signature header is missing', function () {
    rawStripePost('/api/webhooks/stripe', $this->payload)
        ->assertStatus(400);
});

it('returns 401 when signature is invalid', function () {
    $badHeader = stripeSignatureHeader($this->payload, 'whsec_wrong');

    rawStripePost('/api/webhooks/stripe', $this->payload, [
        'Stripe-Signature' => $badHeader,
    ])->assertStatus(401);
});

it('returns 401 when timestamp is outside tolerance', function () {
    $oldTimestamp = time() - 3600;

    $header = stripeSignatureHeader(
        $this->payload,
        'whsec_test',
        $oldTimestamp
    );

    rawStripePost('/api/webhooks/stripe', $this->payload, [
        'Stripe-Signature' => $header,
    ])->assertStatus(401);
});

it('returns 500 when webhook secret is not configured', function () {
    config()->set('services.stripe.webhook_secret', null);

    $header = stripeSignatureHeader($this->payload, 'whsec_test');

    rawStripePost('/api/webhooks/stripe', $this->payload, [
        'Stripe-Signature' => $header,
    ])->assertStatus(500);
});
