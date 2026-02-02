<?php

namespace App\Domain\Webhooks\Controllers;

use App\Domain\Webhooks\Services\StripeWebhookService;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeWebhookService $service)
    {
        $data = $request->json()->all();

        $providerEventId = data_get($data, 'id');

        if (! is_string($providerEventId) || $providerEventId === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Missing provider event id',
            ], 422);
        }

        $event = $service->receiveEvent(
            $providerEventId,
            data_get($data, 'type'),
            $data
        );

        $response = [
            'ok' => true,
            'event_uuid' => $event->uuid,
            'status' => $event->status,
        ];

        if (! $event->wasRecentlyCreated) {
            $response['idempotent'] = true;
        }

        return response()->json($response, 200);
    }
}
