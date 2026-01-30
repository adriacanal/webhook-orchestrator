<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeWebhookController;

Route::post('/webhooks/stripe', StripeWebhookController::class);
