<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'version' => ['sometimes', 'string', 'exists:bible_versions,slug'],
            'book_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $validated['q'];
        $perPage = $validated['per_page'] ?? 25;

        $results = Verse::search($query)
            ->when(isset($validated['version']), function ($search) use ($validated) {
                $search->where('bible_version_slug', $validated['version']);
            })
            ->paginate($perPage);

        // Save search to history
        if ($request->user()) {
            SearchHistory::create([
                'user_id' => $request->user()->id,
                'query' => $query,
                'version_slug' => $validated['version'] ?? null,
                'results_count' => $results->total(),
            ]);
        }

        return response()->json($results);
    }

    /**
     * Get user's recent search history.
     */
    public function history(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        $history = SearchHistory::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'query', 'version_slug', 'results_count', 'created_at']);

        return response()->json(['data' => $history]);
    }

    /**
     * Get search suggestions based on history.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        $suggestions = SearchHistory::where('user_id', $request->user()->id)
            ->when($q, function ($query) use ($q) {
                $query->where('query', 'like', $q . '%');
            })
            ->selectRaw('query, MAX(created_at) as last_searched, COUNT(*) as search_count')
            ->groupBy('query')
            ->orderByDesc('last_searched')
            ->limit(10)
            ->get();

        return response()->json(['data' => $suggestions]);
    }

    /**
     * Clear search history.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        SearchHistory::where('user_id', $request->user()->id)->delete();

        return response()->json(null, 204);
    }
}
