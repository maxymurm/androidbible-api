<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BibleVersionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BibleVersion::active()->orderBy('sort_order');

        if ($request->has('language')) {
            $query->byLanguage($request->input('language'));
        }

        $versions = $query->get([
            'id', 'slug', 'short_name', 'name', 'language', 'language_name',
            'description', 'publisher', 'copyright', 'year',
            'has_old_testament', 'has_new_testament', 'has_apocrypha',
            'verse_count', 'text_direction',
        ]);

        return response()->json(['data' => $versions]);
    }

    public function show(BibleVersion $version): JsonResponse
    {
        $version->load('books');

        return response()->json([
            'data' => $version,
        ]);
    }
}
