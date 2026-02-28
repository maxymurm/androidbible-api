<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\Book;
use Illuminate\Http\JsonResponse;

class ChapterController extends Controller
{
    public function index(BibleVersion $version, Book $book): JsonResponse
    {
        // Return list of chapter numbers with verse counts
        $chapters = $version->verses()
            ->where('book_id', $book->id)
            ->selectRaw('chapter_num, COUNT(*) as verse_count')
            ->groupBy('chapter_num')
            ->orderBy('chapter_num')
            ->get();

        return response()->json(['data' => $chapters]);
    }
}
