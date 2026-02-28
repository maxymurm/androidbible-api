<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    public function pull(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since_version' => ['required', 'integer', 'min:0'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ]);

        $result = $this->syncService->pullEvents(
            $request->user(),
            $validated['since_version'],
            $request->header('X-Device-Id'),
            $validated['limit'] ?? 100
        );

        return response()->json(['data' => $result]);
    }

    public function push(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:100'],
            'events.*.entity_type' => ['required', 'string'],
            'events.*.entity_gid' => ['required', 'string'],
            'events.*.action' => ['required', 'string', 'in:create,update,delete'],
            'events.*.payload' => ['required', 'array'],
            'events.*.changed_fields' => ['sometimes', 'nullable', 'array'],
        ]);

        $deviceId = $request->header('X-Device-Id', 'unknown');

        $result = $this->syncService->pushEvents(
            $request->user(),
            $validated['events'],
            $deviceId
        );

        return response()->json(['data' => $result]);
    }

    public function status(Request $request): JsonResponse
    {
        $result = $this->syncService->getStatus($request->user());
        return response()->json(['data' => $result]);
    }
}
