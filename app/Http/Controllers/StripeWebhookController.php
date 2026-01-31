<?php

namespace App\Http\Controllers;

use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->json()->all();

        $providerEventId = data_get($data, 'id');

        if (! is_string($providerEventId) || $providerEventId === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Missing provider event id',
            ], 422);
        }

        try {
            $event = WebhookEvent::query()->create([
                'uuid' => (string) Str::uuid(),
                'provider' => 'stripe',
                'provider_event_id' => $providerEventId,
                'type' => data_get($data, 'type'),
                'raw_payload' => $data,
                'status' => 'received',
            ]);

            return response()->json([
                'ok' => true,
                'event_uuid' => $event->uuid,
                'status' => $event->status,
            ], 200);
        } catch (QueryException $e) {
            // MySQL duplicate key SQLSTATE = 23000
            $sqlState = $e->errorInfo[0] ?? null;

            if ($sqlState === '23000') {
                $existing = WebhookEvent::query()
                    ->where('provider', 'stripe')
                    ->where('provider_event_id', $providerEventId)
                    ->first();

                return response()->json([
                    'ok' => true,
                    'event_uuid' => $existing?->uuid,
                    'status' => $existing?->status ?? 'received',
                    'idempotent' => true,
                ], 200);
            }

            throw $e;
        }
    }
}
