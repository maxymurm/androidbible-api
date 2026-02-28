<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api and use Sanctum for authentication.
| Public routes (no auth required) are at the top.
| Protected routes require a valid Sanctum token.
|
*/

// ── Public Routes ──────────────────────────────────────────────────────

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    })->name('api.health');

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->name('api.auth.register');
        Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.auth.login');
        Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
        Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->name('api.auth.reset-password');

        // Social/OAuth login
        Route::post('/social/{provider}/token', [\App\Http\Controllers\Api\SocialAuthController::class, 'loginWithToken'])->name('api.auth.social.token');
        Route::get('/social/{provider}/redirect', [\App\Http\Controllers\Api\SocialAuthController::class, 'redirect'])->name('api.auth.social.redirect');
        Route::get('/social/{provider}/callback', [\App\Http\Controllers\Api\SocialAuthController::class, 'callback'])->name('api.auth.social.callback');
    });

    // Public Bible endpoints
    Route::get('/versions', [\App\Http\Controllers\Api\BibleVersionController::class, 'index'])->name('api.versions.index');
    Route::get('/versions/{version}', [\App\Http\Controllers\Api\BibleVersionController::class, 'show'])->name('api.versions.show');
    Route::get('/versions/{version}/books', [\App\Http\Controllers\Api\BookController::class, 'index'])->name('api.books.index');

    // ── Protected Routes ───────────────────────────────────────────────

    Route::middleware('auth:sanctum')->group(function () {

        // Auth - authenticated
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/auth/user', [\App\Http\Controllers\Api\AuthController::class, 'user'])->name('api.auth.user');
        Route::put('/auth/user', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile'])->name('api.auth.update-profile');
        Route::post('/auth/devices', [\App\Http\Controllers\Api\AuthController::class, 'registerDevice'])->name('api.auth.register-device');
        Route::delete('/auth/social/{provider}', [\App\Http\Controllers\Api\SocialAuthController::class, 'disconnect'])->name('api.auth.social.disconnect');

        // Bible Content
        Route::get('/versions/{version}/books/{book}/chapters', [\App\Http\Controllers\Api\ChapterController::class, 'index'])->name('api.chapters.index');
        Route::get('/versions/{version}/books/{book}/chapters/{chapter}', [\App\Http\Controllers\Api\VerseController::class, 'index'])->name('api.verses.index');
        Route::get('/verses/{ari}', [\App\Http\Controllers\Api\VerseController::class, 'showByAri'])->name('api.verses.show-by-ari');
        Route::get('/search', [\App\Http\Controllers\Api\SearchController::class, 'search'])->name('api.search');

        // Cross-references & Footnotes
        Route::get('/versions/{version}/cross-references/{ari}', [\App\Http\Controllers\Api\CrossReferenceController::class, 'forVerse'])->name('api.cross-references.verse');
        Route::get('/versions/{version}/cross-references/chapter/{bookId}/{chapter}', [\App\Http\Controllers\Api\CrossReferenceController::class, 'forChapter'])->name('api.cross-references.chapter');
        Route::get('/versions/{version}/footnotes/{ari}', [\App\Http\Controllers\Api\FootnoteController::class, 'forVerse'])->name('api.footnotes.verse');
        Route::get('/versions/{version}/footnotes/chapter/{bookId}/{chapter}', [\App\Http\Controllers\Api\FootnoteController::class, 'forChapter'])->name('api.footnotes.chapter');

        // Compare versions
        Route::get('/compare/verse/{ari}', [\App\Http\Controllers\Api\CompareController::class, 'compareVerse'])->name('api.compare.verse');
        Route::get('/compare/chapter/{bookId}/{chapter}', [\App\Http\Controllers\Api\CompareController::class, 'compareChapter'])->name('api.compare.chapter');

        // Version Manager
        Route::get('/my-versions', [\App\Http\Controllers\Api\VersionManagerController::class, 'myVersions'])->name('api.my-versions.index');
        Route::post('/versions/{version}/download', [\App\Http\Controllers\Api\VersionManagerController::class, 'download'])->name('api.versions.download');
        Route::delete('/versions/{version}/download', [\App\Http\Controllers\Api\VersionManagerController::class, 'remove'])->name('api.versions.remove');
        Route::post('/versions/{version}/favorite', [\App\Http\Controllers\Api\VersionManagerController::class, 'toggleFavorite'])->name('api.versions.favorite');
        Route::put('/my-versions/reorder', [\App\Http\Controllers\Api\VersionManagerController::class, 'reorder'])->name('api.my-versions.reorder');
        Route::post('/versions/{version}/mark-read', [\App\Http\Controllers\Api\VersionManagerController::class, 'markAsRead'])->name('api.versions.mark-read');

        // Reading History & Navigation
        Route::get('/reading-history', [\App\Http\Controllers\Api\ReadingHistoryController::class, 'index'])->name('api.reading-history.index');
        Route::post('/reading-history', [\App\Http\Controllers\Api\ReadingHistoryController::class, 'record'])->name('api.reading-history.record');
        Route::get('/reading-history/last', [\App\Http\Controllers\Api\ReadingHistoryController::class, 'lastRead'])->name('api.reading-history.last');
        Route::delete('/reading-history', [\App\Http\Controllers\Api\ReadingHistoryController::class, 'clear'])->name('api.reading-history.clear');

        // Search History & Suggestions
        Route::get('/search/history', [\App\Http\Controllers\Api\SearchController::class, 'history'])->name('api.search.history');
        Route::get('/search/suggestions', [\App\Http\Controllers\Api\SearchController::class, 'suggestions'])->name('api.search.suggestions');
        Route::delete('/search/history', [\App\Http\Controllers\Api\SearchController::class, 'clearHistory'])->name('api.search.clear-history');

        // Markers (bookmarks, notes, highlights)
        Route::apiResource('markers', \App\Http\Controllers\Api\MarkerController::class);
        Route::post('/markers/batch', [\App\Http\Controllers\Api\MarkerController::class, 'batchStore'])->name('api.markers.batch-store');
        Route::delete('/markers/batch', [\App\Http\Controllers\Api\MarkerController::class, 'batchDestroy'])->name('api.markers.batch-destroy');
        Route::get('/markers/export/all', [\App\Http\Controllers\Api\MarkerController::class, 'export'])->name('api.markers.export');

        // Labels (categories/tags for markers)
        Route::apiResource('labels', \App\Http\Controllers\Api\LabelController::class);
        Route::post('/markers/{marker}/labels/{label}', [\App\Http\Controllers\Api\MarkerLabelController::class, 'attach'])->name('api.marker-labels.attach');
        Route::delete('/markers/{marker}/labels/{label}', [\App\Http\Controllers\Api\MarkerLabelController::class, 'detach'])->name('api.marker-labels.detach');

        // Progress Marks (pins)
        Route::apiResource('progress-marks', \App\Http\Controllers\Api\ProgressMarkController::class)->except(['destroy']);

        // Reading Plans
        Route::apiResource('reading-plans', \App\Http\Controllers\Api\ReadingPlanController::class)->only(['index', 'show']);
        Route::post('/reading-plans/{readingPlan}/start', [\App\Http\Controllers\Api\ReadingPlanProgressController::class, 'start'])->name('api.reading-plans.start');
        Route::get('/reading-plans/{readingPlan}/progress', [\App\Http\Controllers\Api\ReadingPlanProgressController::class, 'show'])->name('api.reading-plans.progress');
        Route::put('/reading-plans/{readingPlan}/progress', [\App\Http\Controllers\Api\ReadingPlanProgressController::class, 'update'])->name('api.reading-plans.progress.update');

        // Devotionals
        Route::get('/devotionals', [\App\Http\Controllers\Api\DevotionalController::class, 'index'])->name('api.devotionals.index');
        Route::get('/devotionals/today', [\App\Http\Controllers\Api\DevotionalController::class, 'today'])->name('api.devotionals.today');
        Route::get('/devotionals/{devotional}', [\App\Http\Controllers\Api\DevotionalController::class, 'show'])->name('api.devotionals.show');

        // Songs
        Route::get('/songbooks', [\App\Http\Controllers\Api\SongBookController::class, 'index'])->name('api.songbooks.index');
        Route::get('/songbooks/{songBook}', [\App\Http\Controllers\Api\SongBookController::class, 'show'])->name('api.songbooks.show');
        Route::get('/songbooks/{songBook}/songs', [\App\Http\Controllers\Api\SongController::class, 'index'])->name('api.songs.index');
        Route::get('/songs/search', [\App\Http\Controllers\Api\SongController::class, 'search'])->name('api.songs.search');
        Route::get('/songs/{song}', [\App\Http\Controllers\Api\SongController::class, 'show'])->name('api.songs.show');

        // Sync
        Route::prefix('sync')->group(function () {
            Route::get('/pull', [\App\Http\Controllers\Api\SyncController::class, 'pull'])->name('api.sync.pull');
            Route::post('/push', [\App\Http\Controllers\Api\SyncController::class, 'push'])->name('api.sync.push');
            Route::get('/status', [\App\Http\Controllers\Api\SyncController::class, 'status'])->name('api.sync.status');
        });

        // Push Notifications (sync fallback)
        Route::post('/push-notifications/sync-nudge', [\App\Http\Controllers\Api\PushNotificationController::class, 'sendSyncNudge'])->name('api.push.sync-nudge');
        Route::put('/push-notifications/token', [\App\Http\Controllers\Api\PushNotificationController::class, 'updateToken'])->name('api.push.update-token');

        // User Preferences
        Route::get('/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'index'])->name('api.preferences.index');
        Route::put('/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'update'])->name('api.preferences.update');
    });
});
