<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SongBook;
use Illuminate\Http\JsonResponse;

class SongBookController extends Controller
{
    public function index(): JsonResponse
    {
        $books = SongBook::active()->orderBy('sort_order')->get();
        return response()->json(['data' => $books]);
    }

    public function show(SongBook $songBook): JsonResponse
    {
        $songBook->load('songs');
        return response()->json(['data' => $songBook]);
    }
}
