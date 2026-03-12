---
applyTo: '**'
lastUpdated: '2026-03-12'
chatSession: 'session-002'
projectName: 'androidbible-api'
---

# Project Memory  androidbible-api (goldenBowl Laravel Backend)

> **AGENT INSTRUCTIONS:** Always read this file FIRST before starting any new conversation. Update after completing tasks, making decisions, or when user says "remember this".

---

##  Current Focus

**Active Phase:** Phase 13  Sync Protocol & Delta Sync  
**Active Issue:** None (scoping re-done with correct inspiration)  
**Current Branch:** main  
**Last Activity:** 2026-03-12  Re-scoped against actual inspiration: androidbible native Java  BibleCMP (Compose Multiplatform) porting guide. goldenBowl Laravel backend = reference implementation.

**Phases 112 Complete:**
-  Foundation, PostgreSQL schema, Sanctum auth, Bible content API
-  Markers (bookmarks/notes/highlights/labels), Label management
-  Real-time sync via Laravel Reverb WebSocket
-  Reading plans, devotionals, song books
-  VOTD, share, history, push notifications
-  Docker, GitHub Actions CI/CD, Horizon, scheduler
-  OpenAPI 3.0.3 docs, PHPUnit Feature+Unit tests
-  85 original issues closed; all committed and pushed

**What's Next (Phase 13+):**
1. Harden sync protocol to match goldenBowl reference (delta sync with revision tracking)
2. OAuth improvements (Apple JWKS manual verification, Google ID token flow)
3. Broadcasting enhancements (echo prevention device_id, conflict resolution)
4. Version download catalog API (YES2 file downloads, goldenBowl catalog endpoint)
5. Reading Plan sync endpoints
6. Song/Devotion content APIs
7. Production deployment hardening (Forge + DigitalOcean)

---

##  User Preferences

### Tech Stack
- **Backend:** Laravel 11 + PHP 8.4 + PostgreSQL 16 + Redis 7 + Meilisearch + Reverb + Horizon
- **Auth:** Sanctum (Bearer tokens), Socialite (Google/Apple)
- **Queue:** Laravel Horizon
- **Search:** Meilisearch via Laravel Scout
- **Real-time:** Laravel Reverb (Pusher protocol)

### Coding Style
- PSR-12, Laravel conventions, Form Request validation, thin controllers
- Conventional commits with "Closes #N"
- Feature tests + Unit tests, PHPUnit/Pest
- RESTful API under `/api/`
- All responses: `{ "success": true, "data": {...} }` or Laravel Resources

### Git Workflow
- Branch: main  
- Auto-push after every commit

---

##  Project File Map

```
androidbible-api/
 .github/
    instructions/memory.instruction.md   THIS FILE
    ISSUE_TEMPLATE/
    workflows/ci.yml
 agents/                   AI automation templates
    AUTONOMOUS_PROMPT_API.md
 app/
    Http/Controllers/Api/
       Auth/             AuthController, SocialAuthController
       BroadcastingAuthController.php
       SyncController.php
       MarkerController.php
       LabelController.php
       ...
    Models/               User, Marker, Label, MarkerLabel, Device, etc.
    Services/SyncService.php
 database/migrations/      All 12+ migrations
 docs/PROJECT_DOCUMENTATION.md
 routes/
    api.php               All API routes
    channels.php          WebSocket channels
 tests/Feature+Unit/
```

### Feature  File Mapping (goldenBowl reference)
| Feature | File |
|---------|------|
| Auth login/register | `Controllers/Api/Auth/AuthController.php` |
| Google/Apple OAuth | `Controllers/Api/Auth/SocialAuthController.php` |
| Delta sync | `Controllers/Api/SyncController.php` + `Services/SyncService.php` |
| WebSocket auth | `Controllers/Api/BroadcastingAuthController.php` |
| Markers | `Controllers/Api/MarkerController.php` |
| Labels | `Controllers/Api/LabelController.php` |
| Broadcast events | `app/Events/MarkerCreated.php`, `MarkerUpdated.php`, etc. |
| Channel auth | `routes/channels.php` |

---

##  API Reference (goldenBowl style)

**Base URL:** `https://writings.gadsda.com/api` (production)  
**Auth:** `Authorization: Bearer {sanctum_token}`

### Key Endpoints
```
POST /api/auth/login            { user, device_id, access_token }
POST /api/auth/register         same
POST /api/auth/oauth/{provider}  google | apple
POST /api/auth/logout
PUT  /api/auth/change-password
DELETE /api/auth/account

GET  /api/user                  { id, name, email, sync_revision }

POST /api/sync/                 delta sync (send changes, receive changes)
GET  /api/sync/status           { sync_revision, last_sync_at }
GET  /api/sync/full             all user data
GET  /api/sync/delta?since=N    changes since revision N
POST /api/sync/device           register device
GET  /api/sync/devices

POST /api/broadcasting/auth     WebSocket channel auth (Sanctum-based, NOT session)
```

### Sync Request Shape
```json
{
  "revision": 40,
  "device_id": "uuid-v4",
  "sync_set_name": "all",
  "markers": [{ "gid": "...", "action": "upsert|delete", "ari": N, "kind": N, ... }],
  "labels": [{ "gid": "...", "action": "upsert|delete", "title": "...", "ordering": N }],
  "progress_marks": [{ "gid": "...", "action": "upsert|delete", "preset_id": N, "ari": N }]
}
```

### Broadcast Events (Reverb  Pusher protocol)
Channel: `private-user.{userId}`
Events: `marker.created`, `marker.updated`, `marker.deleted`, `label.updated`, `progress.updated`

---

##  Patterns & Architecture

### ARI Encoding
```
ari = (bookId shl 16) or (chapter shl 8) or verse
```

### Sync Protocol (goldenBowl)
```
Client  POST /api/sync/ with changes + client revision
Server  DB::transaction():
  1. Apply client changes
  2. Detect & resolve conflicts (last-write-wins / SyncShadow)
  3. Increment server revision
  4. Return server changes since client revision
  5. Broadcast to other devices via Reverb
Client  Apply server changes in SQLDelight transaction
Client  Update local sync_revision
Other devices  Receive via WebSocket, apply locally
```

### Echo Prevention
- Every sync payload includes `device_id`
- Server broadcasts skip the originating device
- Clients filter incoming WebSocket events by device_id

### Conflict Resolution
- `SyncShadow` table tracks last-known server state per entity
- If both client and server changed the same entity  conflict
- Server wins by default (overwrite with server version + notify client)

### Auth Pattern
```
POST /api/auth/login  Sanctum creates personal access token
Token stored securely client-side (iOS Keychain / Android Keystore)
Sent as: Authorization: Bearer {token}
Tokens don't expire (persist until logout or account deletion)
```

### Apple Sign-In (manual JWKS)
```php
// Server manually verifies Apple identity JWT:
// 1. Fetch JWKS from https://appleid.apple.com/auth/keys
// 2. Find matching key by kid
// 3. Verify JWT signature using RS256
// 4. Validate iss=https://appleid.apple.com, aud=com.app.bundle, exp
// 5. Extract sub (Apple user ID), email
```

---

##  Things to Remember

- **Repo:** https://github.com/maxymurm/androidbible-api
- **Reference backend:** goldenBowl (writings.gadsda.com)
- **Marker kinds:** 1=bookmark, 2=note, 3=highlight (NOT 0-indexed)
- **GIDs:** UUID v4, generated client-side
- **Sync is transactional:** All mutations in a single DB::transaction()
- **Reverb production:** REVERB_HOST=0.0.0.0, REVERB_PORT=8080, behind Nginx proxy
- **Broadcasting auth:** Must use Sanctum Bearer (NOT session cookie) for mobile
- **PHP versions:** 8.4 only (no 8.2/8.3 compatibility needed)

---

##  Project Statistics

**Phases Completed:** 12 of 12 original  
**In Progress:** Phase 13+ (goldenBowl feature alignment)  
**Test Coverage:** 40+ tests (Feature + Unit)  
**API Endpoints:** 50+ routes  
**Eloquent Models:** 18  
**Migrations:** 12  
**Real-time Channels:** private-user.{userId}
