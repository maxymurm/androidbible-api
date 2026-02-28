<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\Book;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function index(BibleVersion $version): JsonResponse
    {
        $books = $version->books()
            ->orderBy('sort_order')
            ->get(['id', 'book_id', 'short_name', 'name', 'chapter_count', 'verse_count', 'testament']);

        return response()->json(['data' => $books]);
    }
}
