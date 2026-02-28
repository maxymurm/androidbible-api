<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\SongBook;
use Illuminate\Http\JsonResponse;

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
}
