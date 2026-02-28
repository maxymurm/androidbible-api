<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    public function index(Request $request): JsonResponse
    {
        $labels = $request->user()->labels()->orderBy('sort_order')->get();
        return response()->json(['data' => $labels]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'background_color' => ['sometimes', 'string', 'max:10'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $label = $request->user()->labels()->create($validated);

        $this->syncService->recordEvent($request->user(), 'label', $label->gid, 'create', $label->toArray(), $request->header('X-Device-Id'));

        return response()->json(['data' => $label], 201);
    }

    public function show(Request $request, Label $label): JsonResponse
    {
        $this->authorize('view', $label);
        $label->load('markers');
        return response()->json(['data' => $label]);
    }

    public function update(Request $request, Label $label): JsonResponse
    {
        $this->authorize('update', $label);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'background_color' => ['sometimes', 'string', 'max:10'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $label->update($validated);

        $this->syncService->recordEvent($request->user(), 'label', $label->gid, 'update', $label->fresh()->toArray(), $request->header('X-Device-Id'));

        return response()->json(['data' => $label]);
    }

    public function destroy(Request $request, Label $label): JsonResponse
    {
        $this->authorize('delete', $label);

        $this->syncService->recordEvent($request->user(), 'label', $label->gid, 'delete', ['gid' => $label->gid], $request->header('X-Device-Id'));
        $label->delete();

        return response()->json(null, 204);
    }
}
