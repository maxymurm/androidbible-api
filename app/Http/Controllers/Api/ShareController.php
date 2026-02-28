<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\Book;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    /**
     * Generate share data for a verse or verse range.
     */
    public function verse(Request $request, int $ari): JsonResponse
    {
        $versionSlug = $request->input('version', 'kjv');
        $version = BibleVersion::where('slug', $versionSlug)->firstOrFail();
        $decoded = Verse::decodeAri($ari);

        $ariEnd = $request->input('ari_end');
        $verses = collect();

        if ($ariEnd) {
            // Range of verses
            $verses = Verse::where('bible_version_id', $version->id)
                ->whereBetween('ari', [$ari, $ariEnd])
                ->orderBy('verse_num')
                ->get(['ari', 'verse_num', 'text']);
        } else {
            // Single verse
            $verse = Verse::where('bible_version_id', $version->id)
                ->where('ari', $ari)
                ->first(['ari', 'verse_num', 'text']);
            if ($verse) $verses->push($verse);
        }

        $book = Book::where('bible_version_id', $version->id)
            ->where('book_id', $decoded['book_id'])
            ->first(['short_name', 'name']);

        $text = $verses->pluck('text')->implode(' ');
        $verseNums = $verses->pluck('verse_num');
        $rangeStr = $verseNums->count() > 1
            ? $verseNums->first() . '-' . $verseNums->last()
            : (string) $verseNums->first();

        $reference = ($book->name ?? '') . ' ' . $decoded['chapter'] . ':' . $rangeStr;

        return response()->json([
            'data' => [
                'reference' => $reference,
                'text' => $text,
                'version' => $version->short_name,
                'share_text' => "\"{$text}\" — {$reference} ({$version->short_name})",
                'share_url' => config('app.url') . '/verse/' . $ari . '?v=' . $versionSlug,
                'image_url' => config('app.url') . '/api/v1/share/verse/' . $ari . '/image?version=' . $versionSlug,
            ],
        ]);
    }

    /**
     * Generate a share image for a verse (placeholder for image generation).
     */
    public function verseImage(Request $request, int $ari)
    {
        $versionSlug = $request->input('version', 'kjv');
        $version = BibleVersion::where('slug', $versionSlug)->firstOrFail();
        $decoded = Verse::decodeAri($ari);

        $verse = Verse::where('bible_version_id', $version->id)
            ->where('ari', $ari)
            ->firstOrFail(['text']);

        $book = Book::where('bible_version_id', $version->id)
            ->where('book_id', $decoded['book_id'])
            ->first(['name']);

        $reference = ($book->name ?? '') . ' ' . $decoded['chapter'] . ':' . $decoded['verse'];

        // Generate simple SVG image
        $text = wordwrap($verse->text, 40, "\n", true);
        $lines = explode("\n", $text);
        $textSvg = '';
        foreach ($lines as $i => $line) {
            $y = 160 + ($i * 36);
            $escaped = htmlspecialchars($line, ENT_XML1);
            $textSvg .= "<text x='400' y='{$y}' text-anchor='middle' font-family='Georgia, serif' font-size='24' fill='#2C3E50'>{$escaped}</text>";
        }
        $refY = 160 + (count($lines) * 36) + 40;

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <rect width="800" height="600" fill="#F5F1EB"/>
  <rect x="30" y="30" width="740" height="540" fill="none" stroke="#8B7355" stroke-width="2" rx="10"/>
  <text x="400" y="80" text-anchor="middle" font-family="Georgia, serif" font-size="16" fill="#8B7355" letter-spacing="4">VERSE OF THE DAY</text>
  <line x1="300" y1="100" x2="500" y2="100" stroke="#8B7355" stroke-width="1"/>
  {$textSvg}
  <text x="400" y="{$refY}" text-anchor="middle" font-family="Georgia, serif" font-size="18" fill="#8B7355" font-style="italic">— {$reference} ({$version->short_name})</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
