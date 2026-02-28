<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\SongBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function index(SongBook $songBook): JsonResponse
    {
        $songs = $songBook->songs()->orderBy('number')->get(['id', 'number', 'title', 'author']);
        return response()->json(['data' => $songs]);
    }

    public function show(Song $song): JsonResponse
    {
        $song->load('songBook');
        return response()->json(['data' => $song]);
    }

    /**
     * Search songs across all song books.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'songbook_id' => ['sometimes', 'integer', 'exists:song_books,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Song::where(function ($q) use ($validated) {
            $q->where('title', 'ilike', '%' . $validated['q'] . '%')
              ->orWhere('lyrics', 'ilike', '%' . $validated['q'] . '%')
              ->orWhere('number', $validated['q']);
        });

        if (isset($validated['songbook_id'])) {
            $query->where('song_book_id', $validated['songbook_id']);
        }

        $songs = $query->with('songBook:id,title')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json($songs);
    }
}
