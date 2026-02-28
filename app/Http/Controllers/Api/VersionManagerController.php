<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleVersion;
use App\Models\UserBibleVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionManagerController extends Controller
{
    /**
     * List user's downloaded/saved versions.
     */
    public function myVersions(Request $request): JsonResponse
    {
        $userVersions = UserBibleVersion::where('user_id', $request->user()->id)
            ->with('bibleVersion:id,slug,short_name,name,language,language_name,file_size')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $userVersions]);
    }

    /**
     * Download (register) a version for the user.
     */
    public function download(Request $request, BibleVersion $version): JsonResponse
    {
        $userVersion = UserBibleVersion::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'bible_version_id' => $version->id,
            ],
            [
                'is_downloaded' => true,
            ]
        );

        // Increment download count
        $version->increment('download_count');

        return response()->json([
            'data' => $userVersion->load('bibleVersion'),
            'download_url' => $version->download_url,
        ], 201);
    }

    /**
     * Remove a downloaded version.
     */
    public function remove(Request $request, BibleVersion $version): JsonResponse
    {
        UserBibleVersion::where('user_id', $request->user()->id)
            ->where('bible_version_id', $version->id)
            ->update(['is_downloaded' => false]);

        return response()->json(null, 204);
    }

    /**
     * Toggle favorite for a version.
     */
    public function toggleFavorite(Request $request, BibleVersion $version): JsonResponse
    {
        $userVersion = UserBibleVersion::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'bible_version_id' => $version->id,
            ]
        );

        $userVersion->update(['is_favorite' => !$userVersion->is_favorite]);

        return response()->json(['data' => $userVersion]);
    }

    /**
     * Reorder user's versions.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*.version_id' => ['required', 'integer', 'exists:bible_versions,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['order'] as $item) {
            UserBibleVersion::where('user_id', $request->user()->id)
                ->where('bible_version_id', $item['version_id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Versions reordered']);
    }

    /**
     * Record version as last read and update timestamp.
     */
    public function markAsRead(Request $request, BibleVersion $version): JsonResponse
    {
        UserBibleVersion::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'bible_version_id' => $version->id,
            ],
            [
                'last_read_at' => now(),
            ]
        );

        return response()->json(['message' => 'Marked as read']);
    }
}
