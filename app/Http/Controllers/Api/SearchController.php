<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json($results);
    }
}
