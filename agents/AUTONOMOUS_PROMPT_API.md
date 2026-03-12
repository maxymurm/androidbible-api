# AUTONOMOUS EXECUTION PROMPT — Android Bible API
## Document Type: Opus-Level Autonomous Execution Prompt
## Version: 1.0
## Date: 2026-03-12
## Track: Backend (Laravel 11 API)

---

## ⚡ YOLO MODE — FULL AUTONOMOUS EXECUTION

You are an expert autonomous AI agent executing work on the **Android Bible API** — a Laravel 11 REST API backend for the Android Bible mobile app.

**DO NOT** ask for clarification. **DO NOT** stop for approval. Work through open issues one by one, committing after each, until ALL issues are resolved or you run out of context.

---

## 🔐 Repository Access

```
Repo:    https://github.com/maxymurm/androidbible-api.git
Branch:  main
Remote:  origin
```

### Before You Start
1. `cd` into the project root
2. Read `.github/instructions/memory.instruction.md` — project state, architecture, patterns
3. Read `docs/PROJECT_DOCUMENTATION.md` — full architecture reference
4. Run `php artisan route:list` to understand existing routes
5. Run `php artisan test` to confirm all tests pass before starting work

---

## ✅ What Has Been Completed (Phases 1–12)

All original 85 Android Bible issues are closed. The following is complete:

- **Laravel 11 foundation**: PHP 8.4, PostgreSQL 16, Redis, Meilisearch, Reverb, Horizon, Sanctum
- **Database schema**: 12 migrations (users, devices, bible_versions, books, chapters, verses, markers, labels, sync_events, reading_plans, devotionals, songs)
- **Auth**: Sanctum token auth, Google + Apple OAuth (Socialite), password reset, device registration
- **Bible content API**: all CRUD endpoints, ARI-based references, cross-refs, footnotes, VOTD
- **Markers system**: unified Marker model (kind: 0=bookmark, 1=note, 2=highlight), GID sync keys, labels
- **Sync engine**: SyncController + SyncService, event sourcing via sync_events, Reverb WebSocket broadcast
- **Reading plans + Devotionals + Songs**: full CRUD APIs
- **Testing**: 40+ PHPUnit tests (Feature + Unit)
- **DevOps**: Docker Compose (8 services), GitHub Actions CI/CD, Horizon, Scheduler
- **Docs**: OpenAPI 3.0.3 at `docs/openapi.yaml`, CONTRIBUTING.md, MIGRATION.md

---

## 📋 Open Issues to Implement

Issues are on GitHub: https://github.com/maxymurm/androidbible-api/issues

### Phase 13: SWORD Module System (Milestone #1)

| # | Title | Labels |
|---|-------|--------|
| #2 | Epic: SWORD Module System | epic, sword, phase-13 |
| #16 | Create modules table and Module Eloquent model | backend, database, sword, phase-13 |
| #18 | CrossWire module catalog API integration | backend, api, sword, phase-13 |
| #20 | SWORD module download and install pipeline | backend, sword, phase-13 |
| #22 | Commentary API endpoints per verse and module | backend, api, sword, phase-13 |
| #24 | Dictionary and Lexicon API endpoints (RawLD4/zLD) | backend, api, sword, phase-13 |
| #26 | Strong's numbers index and lookup API | backend, api, sword, phase-13 |
| #47 | CRDT vector clock sync upgrade | backend, sync, phase-13 |

### Phase 14: Enhanced Annotations (Milestone #2)

| # | Title | Labels |
|---|-------|--------|
| #28 | Pins feature: model, migration, and CRUD API | backend, api, database, phase-14 |
| #30 | Bookmark folders: hierarchical folders for bookmarks | backend, api, database, phase-14 |
| #32 | 6-color highlight palette: extend highlights model | backend, api, phase-14 |
| #34 | Rich-text notes: Markdown storage and content_format | backend, api, database, phase-14 |
| #36 | Tags / collections system for markers | backend, api, database, phase-14 |

### Phase 15: Web Frontend (Milestone #3)

| # | Title | Labels |
|---|-------|--------|
| #37 | Inertia.js + React 19 web frontend setup | backend, phase-15 |
| #38 | Bible reader web page (Inertia/React) | backend, api, phase-15 |
| #39 | User dashboard and markers management web page | backend, phase-15 |

### Phase 16: Advanced Search & Study Tools (Milestone #4)

| # | Title | Labels |
|---|-------|--------|
| #40 | Advanced search: phrase, Strong's, morphology filters | backend, api, phase-16 |
| #41 | Word study API: concordance and all occurrences | backend, api, phase-16 |

### Phase 17: Audio Bible & Media (Milestone #5)

| # | Title | Labels |
|---|-------|--------|
| #45 | Audio Bible module API endpoints | backend, api, sword, phase-17 |
| #46 | Verse image generation endpoint | backend, api, phase-17 |

### Phase 18: Statistics & Export (Milestone #6)

| # | Title | Labels |
|---|-------|--------|
| #42 | Reading history detailed tracking | backend, database, phase-18 |
| #43 | Reading statistics and streaks API | backend, api, phase-18 |
| #44 | Data export: DOCX and PDF generation | backend, api, phase-18 |

---

## 🏗️ Architecture Reference

### Key Patterns

**Controller + FormRequest + Resource pattern:**
```php
// app/Http/Controllers/ExampleController.php
class ExampleController extends Controller
{
    public function __construct(private ExampleService $service) {}
    
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ExampleResource::collection(
                $this->service->getPaginated($request->user())
            )
        ]);
    }
}
```

**Service layer:**
```php
// app/Services/ExampleService.php
class ExampleService
{
    public function getPaginated(User $user): LengthAwarePaginator
    {
        return Example::where('user_id', $user->id)->paginate(20);
    }
}
```

**Routes (api.php — versioned):**
```php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('examples', ExampleController::class);
});
```

**Migrations:**
```php
Schema::create('examples', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('gid')->unique(); // sync key
    $table->timestamps();
    $table->softDeletes();
});
```

**ARI encoding:**
```php
$ari = ($bookId << 16) | ($chapter << 8) | $verse;
```

**Marker kinds:**
- `0` = Bookmark
- `1` = Note
- `2` = Highlight

### Existing Models (reference relationships)
- `User` hasMany `Marker`, `Device`, `SyncEvent`, `ReadingHistory`, `ReadingPlan`
- `Marker` belongsTo `User`, `Verse`; belongsToMany `Label`
- `BibleVersion` hasMany `Book`; `Book` hasMany `Chapter`; `Chapter` hasMany `Verse`

### API Response Format
```json
{ "data": { ... } }
// or paginated:
{ "data": [...], "meta": { "current_page": 1, "total": 100 }, "links": {...} }
```

---

## 🧰 Tools & Environment

```bash
# Docker services
docker-compose up -d

# Run tests
php artisan test

# Run specific test
php artisan test tests/Feature/MarkerTest.php

# Create migration
php artisan make:migration create_examples_table

# Create model with all stubs
php artisan make:model Example -mfsc --api --requests --resource

# Clear caches
php artisan cache:clear && php artisan route:clear && php artisan config:clear

# Git workflow
git add -A
git commit -m "feat: implement feature X (Closes #N)"
git push origin main
```

---

## 📐 Execution Rules

1. **Read the issue** fully before starting any code
2. **Follow TDD** where feasible (write test first, then implement)
3. **One commit per issue** with message `feat: {description} (Closes #N)`
4. **Run `php artisan test`** before EVERY commit. Do not commit failing tests
5. **Update `docs/openapi.yaml`** for every new or changed endpoint
6. **Update `.github/instructions/memory.instruction.md`** after completing each issue
7. **Close the issue** via commit message `Closes #N`
8. **Tag with release** after completing each milestone (`git tag v13.0.0`)
9. **Do not break existing tests** — if you must change existing behavior, update tests too
10. **YOLO** — make decisions. Don't stop to ask. Commit and move on.

---

## 🚦 Implementation Order

Start with Phase 13 issues in order: #16 → #18 → #20 → #22 → #24 → #26 → #47 → #2 (epic close)
Then Phase 14: #28 → #30 → #32 → #34 → #36
Then Phase 15: #37 → #38 → #39
Then Phase 16: #40 → #41
Then Phase 17: #45 → #46
Then Phase 18: #42 → #43 → #44

---

## 🎯 SWORD Module Implementation Guide

The SWORD module system reads binary `.sword` or `.zip` module files. The PHP implementation in PocketSword's backend is the reference. Key file formats:

### zText (compressed Bible)
- `.bzv` — verse index (4 bytes each: block number + verse offset within block)
- `.bzz` — compressed data blocks (zlib)  
- Read block → decompress → extract verse by offset

### RawCom (commentary)
- `.idx` — 6-byte entries: 4-byte offset + 2-byte length
- `.dat` — raw commentary text
- Read offset from idx → read length bytes from dat

### RawLD4 (dictionary)
- `.idx` — sorted list of keys with offsets
- `.dat` — definition text per key
- Binary search on key → read definition

### Module Config (.conf)
```ini
[KJV]
DataPath=./modules/texts/ztext/kjv/
ModDrv=zText
Lang=en
Versification=KJV
Encoding=UTF-8
BlockType=CHAPTER
CompressType=ZIP
```

---

## 📝 Final Note

This is a YOLO autonomous run. You have full authority to:
- Create, modify, delete any files in this repo
- Create migrations and run them
- Commit and push at any point
- Close GitHub issues
- Make architectural decisions within the established patterns

Go. Start with issue #16.
