<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\Book;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VerseOfTheDayController extends Controller
{
    /**
     * Get the Verse of the Day.
     * Cached for 24 hours. Changes daily.
     */
    public function today(Request $request): JsonResponse
    {
        $versionSlug = $request->input('version', 'kjv');
        $cacheKey = "votd:{$versionSlug}:" . now()->format('Y-m-d');

        $votd = Cache::remember($cacheKey, 86400, function () use ($versionSlug) {
            $version = BibleVersion::where('slug', $versionSlug)->firstOrFail();

            // Curated list of popular verses for VOTD
            $popularVerses = $this->getPopularVerseAris();

            // Pick one based on day of year
            $dayOfYear = now()->dayOfYear;
            $ariIndex = $dayOfYear % count($popularVerses);
            $ari = $popularVerses[$ariIndex];

            $verse = Verse::where('bible_version_id', $version->id)
                ->where('ari', $ari)
                ->first(['id', 'ari', 'chapter_num', 'verse_num', 'text', 'text_formatted']);

            if (!$verse) {
                // Fallback to random verse
                $verse = Verse::where('bible_version_id', $version->id)
                    ->inRandomOrder()
                    ->first(['id', 'ari', 'chapter_num', 'verse_num', 'text', 'text_formatted']);
            }

            $decoded = Verse::decodeAri($verse->ari);
            $book = Book::where('bible_version_id', $version->id)
                ->where('book_id', $decoded['book_id'])
                ->first(['short_name', 'name']);

            return [
                'verse' => $verse,
                'book' => $book,
                'version' => $version->short_name,
                'reference' => ($book->name ?? '') . ' ' . $decoded['chapter'] . ':' . $decoded['verse'],
                'date' => now()->format('Y-m-d'),
            ];
        });

        return response()->json(['data' => $votd]);
    }

    /**
     * Curated ARIs of popular Bible verses.
     * ARI = (bookId << 16) | (chapter << 8) | verse
     */
    private function getPopularVerseAris(): array
    {
        return [
            // John 3:16
            (43 << 16) | (3 << 8) | 16,
            // Psalm 23:1
            (19 << 16) | (23 << 8) | 1,
            // Jeremiah 29:11
            (24 << 16) | (29 << 8) | 11,
            // Proverbs 3:5
            (20 << 16) | (3 << 8) | 5,
            // Romans 8:28
            (45 << 16) | (8 << 8) | 28,
            // Philippians 4:13
            (50 << 16) | (4 << 8) | 13,
            // Isaiah 41:10
            (23 << 16) | (41 << 8) | 10,
            // Romans 12:2
            (45 << 16) | (12 << 8) | 2,
            // Matthew 11:28
            (40 << 16) | (11 << 8) | 28,
            // Psalm 46:1
            (19 << 16) | (46 << 8) | 1,
            // Galatians 5:22
            (48 << 16) | (5 << 8) | 22,
            // 2 Timothy 1:7
            (55 << 16) | (1 << 8) | 7,
            // Joshua 1:9
            (6 << 16) | (1 << 8) | 9,
            // Psalm 119:105
            (19 << 16) | (119 << 8) | 105,
            // Romans 10:9
            (45 << 16) | (10 << 8) | 9,
            // 1 Corinthians 13:4
            (46 << 16) | (13 << 8) | 4,
            // Psalm 37:4
            (19 << 16) | (37 << 8) | 4,
            // Proverbs 3:6
            (20 << 16) | (3 << 8) | 6,
            // Hebrews 11:1
            (58 << 16) | (11 << 8) | 1,
            // Matthew 6:33
            (40 << 16) | (6 << 8) | 33,
            // Psalm 27:1
            (19 << 16) | (27 << 8) | 1,
            // Isaiah 40:31
            (23 << 16) | (40 << 8) | 31,
            // Ephesians 2:8
            (49 << 16) | (2 << 8) | 8,
            // Genesis 1:1
            (1 << 16) | (1 << 8) | 1,
            // John 14:6
            (43 << 16) | (14 << 8) | 6,
            // Psalm 34:8
            (19 << 16) | (34 << 8) | 8,
            // 1 Peter 5:7
            (60 << 16) | (5 << 8) | 7,
            // Matthew 28:20
            (40 << 16) | (28 << 8) | 20,
            // James 1:5
            (59 << 16) | (1 << 8) | 5,
            // Psalm 121:1
            (19 << 16) | (121 << 8) | 1,
            // Romans 5:8
            (45 << 16) | (5 << 8) | 8,
            // Colossians 3:23
            (51 << 16) | (3 << 8) | 23,
            // Psalm 139:14
            (19 << 16) | (139 << 8) | 14,
            // Matthew 5:16
            (40 << 16) | (5 << 8) | 16,
            // John 1:1
            (43 << 16) | (1 << 8) | 1,
            // 2 Corinthians 5:17
            (47 << 16) | (5 << 8) | 17,
        ];
    }
}
