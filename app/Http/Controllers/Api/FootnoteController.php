<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\Footnote;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;

class FootnoteController extends Controller
{
    /**
     * Get footnotes for a specific verse (by ARI).
     */
    public function forVerse(BibleVersion $version, int $ari): JsonResponse
    {
        $footnotes = Footnote::where('bible_version_id', $version->id)
            ->where('ari', $ari)
            ->get(['id', 'ari', 'content', 'field']);

        return response()->json(['data' => $footnotes]);
    }

    /**
     * Get all footnotes for a chapter.
     */
    public function forChapter(BibleVersion $version, int $bookId, int $chapter): JsonResponse
    {
        $ariStart = Verse::encodeAri($bookId, $chapter, 0);
        $ariEnd = Verse::encodeAri($bookId, $chapter, 255);

        $footnotes = Footnote::where('bible_version_id', $version->id)
            ->whereBetween('ari', [$ariStart, $ariEnd])
            ->orderBy('ari')
            ->get(['id', 'ari', 'content', 'field']);

        return response()->json(['data' => $footnotes]);
    }
}
