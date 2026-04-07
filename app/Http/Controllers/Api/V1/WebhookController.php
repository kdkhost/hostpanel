<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $token   = $request->attributes->get('api_token');
        $webhooks = Webhook::where('api_token_id', $token?->id)->get();
        return response()->json($webhooks);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'url'    => 'required|url',
            'events' => 'required|array',
        ]);

        $token   = $request->attributes->get('api_token');
        $webhook = Webhook::create([
            'name'         => $request->name,
            'url'          => $request->url,
            'secret'       => $request->secret ?? \Illuminate\Support\Str::random(32),
            'events'       => $request->events,
            'active'       => true,
            'api_token_id' => $token?->id,
        ]);

        return response()->json($webhook, 201);
    }

    public function destroy(Webhook $webhook): JsonResponse
    {
        $webhook->delete();
        return response()->json(['message' => 'Webhook removido.']);
    }
}
