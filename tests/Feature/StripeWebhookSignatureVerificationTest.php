<?php

use Illuminate\Testing\TestResponse;

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

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Envia el body com RAW (important per Stripe).
 */
function rawStripePost(
    string $uri,
    string $rawBody,
    array $headers = []
): TestResponse {
    $server = array_merge([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], arrayToServerHeaders($headers));

    return test()->call('POST', $uri, [], [], [], $server, $rawBody);
}

/**
 * Genera la capçalera Stripe-Signature.
 * Stripe signa "{timestamp}.{payload}" amb HMAC-SHA256.
 */
function stripeSignatureHeader(
    string $payload,
    string $secret,
    ?int $timestamp = null
): string {
    $timestamp ??= time();

    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return "t={$timestamp},v1={$signature}";
}

/**
 * Converteix headers normals a format $_SERVER.
 * Ex: Stripe-Signature => HTTP_STRIPE_SIGNATURE
 */
function arrayToServerHeaders(array $headers): array
{
    $server = [];

    foreach ($headers as $name => $value) {
        $key = 'HTTP_'.strtoupper(str_replace('-', '_', $name));
        $server[$key] = $value;
    }

    return $server;
}
