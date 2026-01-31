<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');
        $tolerance = (int) config('services.stripe.webhook_tolerance', 300);

        if ($secret === '') {
            return response()->json(['message' => 'Stripe webhook secret not configured.'], 500);
        }

        $signature = $request->header('Stripe-Signature');
        if (! $signature) {
            return response()->json(['message' => 'Missing Stripe-Signature header.'], 400);
        }

        try {
            Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $secret,
                $tolerance,
            );
        } catch (SignatureVerificationException) {
            return response()->json(['message' => 'Invalid Stripe signature.'], 401);
        } catch (\UnexpectedValueException) {
            return response()->json(['message' => 'Invalid Stripe payload.'], 400);
        }

        return $next($request);
    }
}
