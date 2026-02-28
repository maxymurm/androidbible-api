<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgressMark;
use App\Models\ProgressMarkHistory;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressMarkController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    public function index(Request $request): JsonResponse
    {
        $marks = $request->user()->progressMarks()->orderBy('preset_id')->get();
        return response()->json(['data' => $marks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preset_id' => ['required', 'integer', 'min:0', 'max:4'],
            'ari' => ['required', 'integer'],
            'bible_version_slug' => ['sometimes', 'nullable', 'string'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $mark = $request->user()->progressMarks()->updateOrCreate(
            ['preset_id' => $validated['preset_id']],
            $validated
        );

        // Record history
        ProgressMarkHistory::create([
            'user_id' => $request->user()->id,
            'progress_mark_id' => $mark->id,
            'ari' => $mark->ari,
            'bible_version_slug' => $mark->bible_version_slug,
        ]);

        $this->syncService->recordEvent($request->user(), 'progress_mark', $mark->gid, 'update', $mark->toArray(), $request->header('X-Device-Id'));

        return response()->json(['data' => $mark], 201);
    }

    public function show(Request $request, ProgressMark $progressMark): JsonResponse
    {
        $this->authorize('view', $progressMark);
        $progressMark->load('history');
        return response()->json(['data' => $progressMark]);
    }

    public function update(Request $request, ProgressMark $progressMark): JsonResponse
    {
        $this->authorize('update', $progressMark);

        $validated = $request->validate([
            'ari' => ['required', 'integer'],
            'bible_version_slug' => ['sometimes', 'nullable', 'string'],
            'caption' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $progressMark->update($validated);

        ProgressMarkHistory::create([
            'user_id' => $request->user()->id,
            'progress_mark_id' => $progressMark->id,
            'ari' => $progressMark->ari,
            'bible_version_slug' => $progressMark->bible_version_slug,
        ]);

        $this->syncService->recordEvent($request->user(), 'progress_mark', $progressMark->gid, 'update', $progressMark->fresh()->toArray(), $request->header('X-Device-Id'));

        return response()->json(['data' => $progressMark]);
    }
}
