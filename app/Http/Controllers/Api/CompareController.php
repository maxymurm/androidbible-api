<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\Book;
use App\Models\Pericope;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    /**
     * Compare a verse across multiple Bible versions.
     */
    public function compareVerse(Request $request, int $ari): JsonResponse
    {
        $validated = $request->validate([
            'versions' => ['required', 'string'], // comma-separated slugs
        ]);

        $slugs = array_map('trim', explode(',', $validated['versions']));
        $versions = BibleVersion::whereIn('slug', $slugs)->active()->get();

        $decoded = Verse::decodeAri($ari);
        $comparisons = [];

        foreach ($versions as $version) {
            $verse = Verse::where('bible_version_id', $version->id)
                ->where('ari', $ari)
                ->first(['id', 'ari', 'verse_num', 'text', 'text_formatted']);

            $book = Book::where('bible_version_id', $version->id)
                ->where('book_id', $decoded['book_id'])
                ->first(['short_name', 'name']);

            $comparisons[] = [
                'version' => [
                    'slug' => $version->slug,
                    'short_name' => $version->short_name,
                    'name' => $version->name,
                    'language' => $version->language,
                ],
                'book' => $book ? [
                    'short_name' => $book->short_name,
                    'name' => $book->name,
                ] : null,
                'verse' => $verse,
            ];
        }

        return response()->json([
            'data' => [
                'ari' => $ari,
                'decoded' => $decoded,
                'comparisons' => $comparisons,
            ],
        ]);
    }

    /**
     * Compare an entire chapter across multiple Bible versions.
     */
    public function compareChapter(Request $request, int $bookId, int $chapter): JsonResponse
    {
        $validated = $request->validate([
            'versions' => ['required', 'string'], // comma-separated slugs
        ]);

        $slugs = array_map('trim', explode(',', $validated['versions']));
        $versions = BibleVersion::whereIn('slug', $slugs)->active()->get();

        $comparisons = [];

        foreach ($versions as $version) {
            $book = Book::where('bible_version_id', $version->id)
                ->where('book_id', $bookId)
                ->first(['id', 'short_name', 'name']);

            if (!$book) {
                $comparisons[] = [
                    'version' => $version->short_name,
                    'verses' => [],
                ];
                continue;
            }

            $verses = Verse::where('bible_version_id', $version->id)
                ->where('book_id', $book->id)
                ->where('chapter_num', $chapter)
                ->orderBy('verse_num')
                ->get(['id', 'ari', 'verse_num', 'text', 'text_formatted']);

            $pericopes = Pericope::where('bible_version_id', $version->id)
                ->whereBetween('ari', [
                    Verse::encodeAri($bookId, $chapter, 0),
                    Verse::encodeAri($bookId, $chapter, 255),
                ])
                ->orderBy('ari')
                ->get(['ari', 'title']);

            $comparisons[] = [
                'version' => [
                    'slug' => $version->slug,
                    'short_name' => $version->short_name,
                ],
                'book' => $book->name,
                'chapter' => $chapter,
                'verses' => $verses,
                'pericopes' => $pericopes,
            ];
        }

        return response()->json([
            'data' => [
                'book_id' => $bookId,
                'chapter' => $chapter,
                'comparisons' => $comparisons,
            ],
        ]);
    }
}
