# AUTONOMOUS EXECUTION PROMPT  androidbible-api (goldenBowl Laravel Backend)

> **FOR AI AGENTS:** This document instructs you on how to autonomously develop the androidbible-api Laravel backend. Read this COMPLETELY before starting any task.

---

## Project Identity

- **Repository:** https://github.com/maxymurm/androidbible-api
- **Type:** Laravel 11 REST API + WebSocket backend
- **Reference implementation:** goldenBowl (writings.gadsda.com)
- **Mobile client:** androidbible-kmp (BibleCMP Compose Multiplatform)
- **Original Android app:** androidbible (Java/Kotlin)

---

## Mission

Build the **goldenBowl** feature set in the androidbible-api Laravel backend. The reference is a production Laravel 11 app that provides:
1. Sanctum auth (email/password + Google OAuth + Apple Sign-In with JWKS verification)
2. Delta sync protocol (POST /api/sync/ with revision tracking + SyncShadow conflict detection)
3. Real-time broadcasting via Reverb (Pusher protocol, Sanctum-authenticated channels)
4. Version catalog API (YES2 Bible file downloads)
5. Content APIs (reading plans, devotions, songs)

**Phases 112 are complete.** Continue from Phase 13 as specified below.

---

## Operating Rules

1. **Read memory.instruction.md FIRST**  `.github/instructions/memory.instruction.md`
2. **One issue at a time.** Pick ONE GitHub issue, complete it fully, commit, push.
3. **Update memory file** after every significant task or decision.
4. **Thin controllers.** Move all business logic to Service classes.
5. **Form Request validation.** Never validate in controllers directly.
6. **Laravel conventions.** Follow Laravel idioms, PSR-12, use Eloquent properly.
7. **Commit format:** `feat(scope): description [Closes #N]`
8. **Never break existing tests.** Run `php artisan test` before every commit.
9. **Sanctum only.** No JWT packages, no Passport. Sanctum Bearer tokens.
10. **Sync is transactional.** Always wrap sync mutations in `DB::transaction()`.

---

## Architecture Reference

### Auth Flow (goldenBowl)
```php
// Email/password login
POST /api/auth/login
{email, password, device_id, device_name, device_type}
 creates Sanctum personal access token
 registers Device if new
 returns {user, device_id, access_token, token_type: "Bearer"}

// Google OAuth
POST /api/auth/oauth/google
{token: "<Google ID token>", device_id, device_name, device_type}
 Socialite::driver('google')->userFromToken($idToken)
 findOrCreate User by email
 return Sanctum token

// Apple Sign-In
POST /api/auth/oauth/apple
{token: "<Apple identity JWT>", name, device_id, device_name, device_type}
 Fetch JWKS from https://appleid.apple.com/auth/keys
 Verify JWT signature (RS256) using matching kid
 Validate: iss=https://appleid.apple.com, aud=bundle_id, exp
 Extract sub (Apple user ID), email
 findOrCreate User by provider+sub
 return Sanctum token
```

### Sync Flow (goldenBowl)
```php
// SyncController.php
POST /api/sync/
Auth: Sanctum Bearer

Request: {revision: N, device_id: UUID, sync_set_name, markers[], labels[], progress_marks[]}

DB::transaction(function() use ($request, $user) {
    // 1. Collect server changes since client revision
    $serverChanges = $syncService->getChangesSince($user, $request->revision);
    
    // 2. Apply client changes
    foreach ($request->markers as $item) {
        if ($item->action === 'upsert') {
            $syncService->upsertMarker($user, $item);
        } elseif ($item->action === 'delete') {
            $syncService->deleteMarker($user, $item->gid);
        }
    }
    // same for labels, progress_marks
    
    // 3. Increment server revision
    $newRevision = $syncService->incrementRevision($user);
    
    // 4. Broadcast to other devices (skip sender via device_id)
    event(new SyncCompleted($user, $request->device_id, $serverChanges));
    
    return response()->json([
        'success' => true,
        'server_revision' => $newRevision,
        'markers' => $serverChanges->markers,
        'labels' => $serverChanges->labels,
        'progress_marks' => $serverChanges->progress_marks,
    ]);
});
```

### Broadcasting Auth (Sanctum, NOT session)
```php
// BroadcastingAuthController.php
// Must use Sanctum Bearer token authentication, NOT web session
// Mobile clients don't have session cookies

Route::post('/api/broadcasting/auth', function (Request $request) {
    $request->validate(['socket_id' => 'required', 'channel_name' => 'required']);
    $user = $request->user(); // resolved via Sanctum Bearer
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

// routes/channels.php
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

### Conflict Resolution (SyncShadow)
```php
// SyncShadow tracks the last-known server state for each entity
// If client AND server both changed the same entity since last sync:
//    Server version wins (overwrite)
//    Create SyncConflict record for audit

// SyncShadow model: {user_id, entity_type, entity_gid, last_revision}
```

---

## Database Schema

```sql
-- Key tables:
users              (id, name, email, password, sync_revision, last_sync_at)
personal_access_tokens (Sanctum default)
devices            (id, user_id, device_id UUID, device_name, device_type, platform_version, app_version)
markers            (id, gid UUID, user_id, ari INT, kind INT, caption, verse_count, created_at, updated_at, deleted_at)
labels             (id, gid UUID, user_id, title, ordering, background_color, created_at, updated_at)
marker_label       (marker_id, label_id)
progress_marks     (id, gid UUID, user_id, preset_id, caption, ari INT, updated_at)
sync_shadows       (id, user_id, entity_type, entity_gid, last_revision)
sync_logs          (id, user_id, event, details, created_at)
reading_plans      (id, name, title, description, duration, start_time)
reading_plan_progress (id, user_id, reading_plan_id, reading_code, check_time)
songs              (id, number, title, lyrics)
devotions          (id, date, title, content, ari)
```

### ARI Encoding
```
ari = (bookId << 16) | (chapter << 8) | verse
Stored as plain INT in PostgreSQL
```

### Marker Kinds
```
1 = Bookmark
2 = Note
3 = Highlight
```

---

## Phase Execution Plan

### CURRENT: Phase 13  Sync Protocol Hardening (~10 issues)
**Milestone:** "Phase 13: Sync Protocol"

1. `[BE-S-01]` `SyncController`: implement `POST /api/sync/` with DB::transaction
2. `[BE-S-02]` `SyncService`: `getChangesSince($user, $revision)`  query markers/labels/progress since revision
3. `[BE-S-03]` `SyncService`: `upsertMarker/deleteMarker/upsertLabel/upsertProgress`
4. `[BE-S-04]` `SyncShadow` model + migration for conflict detection
5. `[BE-S-05]` Echo prevention: skip broadcasting to originating device_id
6. `[BE-S-06]` `GET /api/sync/delta?since=N` endpoint
7. `[BE-S-07]` `GET /api/sync/full` endpoint
8. `[BE-S-08]` `POST/GET/DELETE /api/sync/device(s)` for device registration
9. `[BE-S-09]` Rate limiting: `throttle:sync` on sync endpoints
10. `[BE-S-10]` Feature tests: sync push/pull round-trip, conflict detection

### Phase 14  Real-time Broadcasting (~8 issues)
**Milestone:** "Phase 14: Broadcasting & Realtime"

1. `[BE-RT-01]` `BroadcastingAuthController` using Sanctum Bearer (NOT session)
2. `[BE-RT-02]` `MarkerCreated`, `MarkerUpdated`, `MarkerDeleted` broadcast events
3. `[BE-RT-03]` `LabelUpdated` broadcast event
4. `[BE-RT-04]` `ProgressUpdated` broadcast event
5. `[BE-RT-05]` Channel definition: `private-user.{userId}` with Sanctum auth
6. `[BE-RT-06]` Reverb production config (REVERB_HOST, REVERB_PORT, Nginx proxy)
7. `[BE-RT-07]` End-to-end WebSocket broadcast test
8. `[BE-RT-08]` Rate limiting on `/api/broadcasting/auth`

### Phase 15  Authentication Enhancements (~8 issues)
**Milestone:** "Phase 15: Auth Enhancements"

1. `[BE-A-01]` Apple Sign-In: manual JWKS JWT verification (NOT Socialite)
2. `[BE-A-02]` `POST /api/auth/forgot-password`  queue mail with reset link
3. `[BE-A-03]` `POST /api/auth/reset-password` with token validation
4. `[BE-A-04]` `DELETE /api/auth/account`  cascade delete all user data
5. `[BE-A-05]` `GET /api/user` with `sync_revision` + `last_sync_at`
6. `[BE-A-06]` Multi-device: list/revoke tokens
7. `[BE-A-07]` Device type field (`android | ios | web` enum)
8. `[BE-A-08]` Feature tests for all auth edge cases

### Phase 16  Version Catalog & Downloads (~8 issues)
**Milestone:** "Phase 16: Version Downloads"

1. `[BE-V-01]` `Version` model + migration (locale, shortName, longName, filename, fileSize, description)
2. `[BE-V-02]` `GET /api/versions`  catalog with language grouping
3. `[BE-V-03]` `GET /api/versions/{id}/download`  signed URL or direct stream
4. `[BE-V-04]` Storage: configure Laravel Storage for YES2 file assets
5. `[BE-V-05]` Version metadata API (bundled/internal version flag)
6. `[BE-V-06]` Admin seeder: seed initial version catalog from YES2 files
7. `[BE-V-07]` CDN integration (optional: S3 or local filesystem)
8. `[BE-V-08]` Feature test: version catalog retrieval

### Phase 17  Content APIs (~10 issues)
**Milestone:** "Phase 17: Content APIs"

1. `[BE-C-01]` `GET /api/reading-plans` with daily assignments
2. `[BE-C-02]` `POST /api/reading-plans/{id}/progress`  mark reading complete
3. `[BE-C-03]` `GET /api/devotions/today` + `GET /api/devotions/{date}`
4. `[BE-C-04]` `GET /api/songs` + `GET /api/songs/{id}`
5. `[BE-C-05]` Song search endpoint
6. `[BE-C-06]` VOTD: date-based selection with category
7. `[BE-C-07]` `GET /api/health`  system health check (DB/Redis/Reverb)
8. `[BE-C-08]` CORS config for iOS/desktop clients
9. `[BE-C-09]` API rate limits review and documentation
10. `[BE-C-10]` OpenAPI spec update for all new endpoints

### Phase 18  Production Deployment (~8 issues)
**Milestone:** "Phase 18: Production"

1. `[BE-D-01]` Laravel Forge environment setup (PHP 8.4, Nginx, PostgreSQL)
2. `[BE-D-02]` Production `.env` checklist (APP_DEBUG=false, REVERB_HOST, etc.)
3. `[BE-D-03]` Nginx config: PHP-FPM + Reverb WebSocket proxy
4. `[BE-D-04]` SSL/TLS with Let's Encrypt (Forge automated)
5. `[BE-D-05]` Horizon secured at `/horizon` (middleware: auth + role)
6. `[BE-D-06]` PostgreSQL automated backup
7. `[BE-D-07]` Zero-downtime deployment (GitHub Actions  Forge API trigger)
8. `[BE-D-08]` Sentry error monitoring integration

---

## Step-by-Step Autonomous Workflow

```
LOOP:
  1. Read memory.instruction.md
  2. Run: gh issue list --repo maxymurm/androidbible-api --state open --limit 20
  3. Pick the LOWEST numbered open issue (highest priority)
  4. Read the issue body fully
  5. Check out feature branch: git checkout -b feat/issue-N-short-description
  6. Implement the feature following patterns above
  7. Write/update Feature test
  8. Run: php artisan test (must pass)
  9. Run: ./vendor/bin/pint (linting)
  10. Commit: git commit -m "feat(scope): description [Closes #N]"
  11. Push: git push origin feat/issue-N-short-description
  12. Create PR (or merge to main if small fix)
  13. Update memory.instruction.md (Active Issue, Recent Decisions)
  14. GOTO 1
```

---

## Anti-Patterns (Never Do)

-  Don't put business logic in Controllers  use Services
-  Don't use `Request::all()` or `$request->all()` without validation
-  Don't authenticate WebSocket channels with session cookies  use Sanctum Bearer
-  Don't use SWORD/CrossWire modules  YES2 binary format only
-  Don't sync outside a DB transaction
-  Don't expire Sanctum tokens automatically
-  Don't use `dd()` or `dump()` in production code
-  Don't skip echo prevention  always check device_id in broadcasts
-  Don't create a web frontend (this is an API-only backend)
