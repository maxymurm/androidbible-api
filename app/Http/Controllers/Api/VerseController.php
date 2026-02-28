<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\Book;
use App\Models\Pericope;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerseController extends Controller
{
    public function index(Request $request, BibleVersion $version, Book $book, int $chapter): JsonResponse
    {
        $verses = Verse::where('bible_version_id', $version->id)
            ->where('book_id', $book->id)
            ->where('chapter_num', $chapter)
            ->orderBy('verse_num')
            ->get(['id', 'ari', 'verse_num', 'text', 'text_formatted']);

        // Get pericopes (section headings) for this chapter
        $ariStart = Verse::encodeAri($book->book_id, $chapter, 0);
        $ariEnd = Verse::encodeAri($book->book_id, $chapter, 255);

        $pericopes = Pericope::where('bible_version_id', $version->id)
            ->whereBetween('ari', [$ariStart, $ariEnd])
            ->orderBy('ari')
            ->get(['ari', 'title']);

        // Get user's markers for this chapter if authenticated
        $markers = [];
        if ($request->user()) {
            $verseAris = $verses->pluck('ari');
            $markers = $request->user()->markers()
                ->whereIn('ari', $verseAris)
                ->get(['id', 'gid', 'kind', 'ari', 'ari_end', 'caption', 'highlight_color']);
        }

        return response()->json([
            'data' => [
                'version' => $version->short_name,
                'book' => $book->name,
                'chapter' => $chapter,
                'verses' => $verses,
                'pericopes' => $pericopes,
                'markers' => $markers,
            ],
        ]);
    }

    public function showByAri(Request $request, int $ari): JsonResponse
    {
        $decoded = Verse::decodeAri($ari);

        $versionSlug = $request->input('version', 'kjv');
        $version = BibleVersion::where('slug', $versionSlug)->firstOrFail();

        $verse = Verse::where('bible_version_id', $version->id)
            ->where('ari', $ari)
            ->firstOrFail(['id', 'ari', 'chapter_num', 'verse_num', 'text', 'text_formatted']);

        $book = Book::where('bible_version_id', $version->id)
            ->where('book_id', $decoded['book_id'])
            ->first(['id', 'book_id', 'short_name', 'name']);

        return response()->json([
            'data' => [
                'verse' => $verse,
                'book' => $book,
                'version' => $version->short_name,
                'decoded_ari' => $decoded,
            ],
        ]);
    }
}
