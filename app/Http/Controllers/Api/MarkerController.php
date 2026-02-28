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

    /**
     * Batch create markers (e.g., multi-verse highlight).
     */
    public function batchStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'markers' => ['required', 'array', 'min:1', 'max:100'],
            'markers.*.kind' => ['required', 'integer', 'in:0,1,2'],
            'markers.*.ari' => ['required', 'integer'],
            'markers.*.ari_end' => ['sometimes', 'nullable', 'integer'],
            'markers.*.bible_version_slug' => ['sometimes', 'nullable', 'string'],
            'markers.*.caption' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'markers.*.highlight_color' => ['sometimes', 'nullable', 'integer'],
            'markers.*.verse_count' => ['sometimes', 'integer', 'min:1'],
            'markers.*.label_ids' => ['sometimes', 'array'],
        ]);

        $created = [];
        foreach ($validated['markers'] as $data) {
            $labelIds = $data['label_ids'] ?? [];
            unset($data['label_ids']);

            $marker = $request->user()->markers()->create($data);
            if (!empty($labelIds)) {
                $marker->labels()->sync($labelIds);
            }

            $this->syncService->recordEvent(
                $request->user(), 'marker', $marker->gid, 'create',
                $marker->toArray(), $request->header('X-Device-Id')
            );

            $created[] = $marker->load('labels');
        }

        return response()->json(['data' => $created], 201);
    }

    /**
     * Batch delete markers.
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $markers = $request->user()->markers()->whereIn('id', $validated['ids'])->get();

        foreach ($markers as $marker) {
            $this->syncService->recordEvent(
                $request->user(), 'marker', $marker->gid, 'delete',
                ['gid' => $marker->gid], $request->header('X-Device-Id')
            );
            $marker->delete();
        }

        return response()->json(null, 204);
    }

    /**
     * Export all user markers as JSON.
     */
    public function export(Request $request): JsonResponse
    {
        $markers = $request->user()->markers()
            ->with('labels')
            ->orderByDesc('marker_date')
            ->get();

        return response()->json([
            'data' => $markers,
            'meta' => [
                'exported_at' => now()->toIso8601String(),
                'total' => $markers->count(),
                'bookmarks' => $markers->where('kind', Marker::KIND_BOOKMARK)->count(),
                'notes' => $markers->where('kind', Marker::KIND_NOTE)->count(),
                'highlights' => $markers->where('kind', Marker::KIND_HIGHLIGHT)->count(),
            ],
        ]);
    }
}
