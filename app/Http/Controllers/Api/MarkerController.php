<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkerController extends Controller
{
    public function __construct(
        private readonly SyncService $syncService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->markers()->with('labels');

        if ($request->has('kind')) {
            $query->where('kind', $request->input('kind'));
        }

        if ($request->has('ari')) {
            $query->where('ari', $request->input('ari'));
        }

        if ($request->has('version')) {
            $query->forVersion($request->input('version'));
        }

        $markers = $query->orderByDesc('marker_date')->paginate($request->input('per_page', 25));

        return response()->json($markers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', 'integer', 'in:0,1,2'],
            'ari' => ['required', 'integer'],
            'ari_end' => ['sometimes', 'nullable', 'integer'],
            'bible_version_slug' => ['sometimes', 'nullable', 'string'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'highlight_color' => ['sometimes', 'nullable', 'integer'],
            'verse_count' => ['sometimes', 'integer', 'min:1'],
            'marker_date' => ['sometimes', 'date'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
        ]);

        $marker = $request->user()->markers()->create($validated);

        if (isset($validated['label_ids'])) {
            $marker->labels()->sync($validated['label_ids']);
        }

        $this->syncService->recordEvent(
            $request->user(),
            'marker',
            $marker->gid,
            'create',
            $marker->toArray(),
            $request->header('X-Device-Id')
        );

        $marker->load('labels');

        return response()->json(['data' => $marker], 201);
    }

    public function show(Request $request, Marker $marker): JsonResponse
    {
        $this->authorize('view', $marker);
        $marker->load('labels');

        return response()->json(['data' => $marker]);
    }

    public function update(Request $request, Marker $marker): JsonResponse
    {
        $this->authorize('update', $marker);

        $validated = $request->validate([
            'caption' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'highlight_color' => ['sometimes', 'nullable', 'integer'],
            'ari' => ['sometimes', 'integer'],
            'ari_end' => ['sometimes', 'nullable', 'integer'],
            'verse_count' => ['sometimes', 'integer', 'min:1'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
        ]);

        $changedFields = array_keys(array_diff_assoc(
            array_intersect_key($validated, $marker->getAttributes()),
            $marker->getAttributes()
        ));

        $marker->update($validated);

        if (isset($validated['label_ids'])) {
            $marker->labels()->sync($validated['label_ids']);
        }

        $this->syncService->recordEvent(
            $request->user(),
            'marker',
            $marker->gid,
            'update',
            $marker->fresh()->toArray(),
            $request->header('X-Device-Id'),
            $changedFields
        );

        $marker->load('labels');

        return response()->json(['data' => $marker]);
    }

    public function destroy(Request $request, Marker $marker): JsonResponse
    {
        $this->authorize('delete', $marker);

        $this->syncService->recordEvent(
            $request->user(),
            'marker',
            $marker->gid,
            'delete',
            ['gid' => $marker->gid],
            $request->header('X-Device-Id')
        );

        $marker->delete(); // Soft delete

        return response()->json(null, 204);
    }
}
