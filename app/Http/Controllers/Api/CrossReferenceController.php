<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\CrossReference;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;

class CrossReferenceController extends Controller
{
    /**
     * Get cross-references for a specific verse (by ARI).
     */
    public function forVerse(BibleVersion $version, int $ari): JsonResponse
    {
        $crossRefs = CrossReference::where('bible_version_id', $version->id)
            ->where('from_ari', $ari)
            ->get(['id', 'from_ari', 'to_ari', 'to_ari_end']);

        // Resolve target verse text for each cross-reference
        $enriched = $crossRefs->map(function ($ref) use ($version) {
            $decoded = Verse::decodeAri($ref->to_ari);
            $targetVerse = Verse::where('bible_version_id', $version->id)
                ->where('ari', $ref->to_ari)
                ->first(['ari', 'chapter_num', 'verse_num', 'text']);

            return [
                'id' => $ref->id,
                'from_ari' => $ref->from_ari,
                'to_ari' => $ref->to_ari,
                'to_ari_end' => $ref->to_ari_end,
                'target_book_id' => $decoded['book_id'],
                'target_chapter' => $decoded['chapter'],
                'target_verse' => $decoded['verse'],
                'target_text' => $targetVerse?->text,
            ];
        });

        return response()->json(['data' => $enriched]);
    }

    /**
     * Get cross-references for an entire chapter.
     */
    public function forChapter(BibleVersion $version, int $bookId, int $chapter): JsonResponse
    {
        $ariStart = Verse::encodeAri($bookId, $chapter, 0);
        $ariEnd = Verse::encodeAri($bookId, $chapter, 255);

        $crossRefs = CrossReference::where('bible_version_id', $version->id)
            ->whereBetween('from_ari', [$ariStart, $ariEnd])
            ->orderBy('from_ari')
            ->get(['id', 'from_ari', 'to_ari', 'to_ari_end']);

        return response()->json(['data' => $crossRefs]);
    }
}
