<?php

use App\Domain\Webhooks\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('stripe.signature');
