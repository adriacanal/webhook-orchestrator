<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;

if (! function_exists('rawStripePost')) {
    /**
     * Envia el body com RAW (Stripe signa exactament el raw payload).
     *
     * @param  array<string, string>  $headers
     */
    function rawStripePost(string $uri, string $rawBody, array $headers = []): TestResponse
    {
        $server = array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json',
        ], arrayToServerHeaders($headers));

        return test()->call('POST', $uri, [], [], [], $server, $rawBody);
    }
}

if (! function_exists('stripeSignatureHeader')) {
    /**
     * Genera la capçalera Stripe-Signature: t=...,v1=...
     */
    function stripeSignatureHeader(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t={$timestamp},v1={$signature}";
    }
}

if (! function_exists('arrayToServerHeaders')) {
    /**
     * Converteix headers normals a format $_SERVER.
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    function arrayToServerHeaders(array $headers): array
    {
        $server = [];

        foreach ($headers as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $server[$key] = $value;
        }

        return $server;
    }
}
