<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingHistory;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingHistoryController extends Controller
{
    /**
     * Get user's reading history (recently read chapters).
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        $history = ReadingHistory::where('user_id', $request->user()->id)
            ->with('bibleVersion:id,slug,short_name')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $history]);
    }

    /**
     * Record or update reading position.
     */
    public function record(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bible_version_id' => ['required', 'integer', 'exists:bible_versions,id'],
            'book_id' => ['required', 'integer'],
            'chapter_num' => ['required', 'integer', 'min:1'],
            'scroll_position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $ari = Verse::encodeAri($validated['book_id'], $validated['chapter_num'], 0);

        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'bible_version_id' => $validated['bible_version_id'],
                'book_id' => $validated['book_id'],
                'chapter_num' => $validated['chapter_num'],
            ],
            [
                'ari' => $ari,
                'scroll_position' => $validated['scroll_position'] ?? 0,
            ]
        );

        return response()->json(['data' => $history]);
    }

    /**
     * Get the last read position.
     */
    public function lastRead(Request $request): JsonResponse
    {
        $history = ReadingHistory::where('user_id', $request->user()->id)
            ->with('bibleVersion:id,slug,short_name')
            ->orderByDesc('updated_at')
            ->first();

        return response()->json(['data' => $history]);
    }

    /**
     * Clear reading history.
     */
    public function clear(Request $request): JsonResponse
    {
        ReadingHistory::where('user_id', $request->user()->id)->delete();

        return response()->json(null, 204);
    }
}
