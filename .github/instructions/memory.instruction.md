---
applyTo: '**'
lastUpdated: '2026-03-12 00:00'
chatSession: 'session-001'
projectName: 'Android Bible API'
---

# Project Memory - Android Bible API

> **AGENT INSTRUCTIONS:** Always read this file FIRST before starting any new conversation. Update after completing tasks, making decisions, or when user says "remember this".

---

## 🎯 Current Focus

**Active Phase:** Phase 13 — Feature Parity & Enhancement (inspired by PocketSword)
**Active Issue:** None (scoping just completed)
**Current Branch:** main
**Last Activity:** 2026-03-12 — Full project initialization, agent setup, new issues created

**What Was Accomplished (Phases 1-12):**
- ✅ Phase 1-2: Laravel foundation, PostgreSQL schema, migrations (12 tables)
- ✅ Phase 3: Sanctum auth, social auth (Google/Apple), password reset
- ✅ Phase 4: Bible content API (versions, books, chapters, verses, cross-refs, footnotes)
- ✅ Phase 5: Markers system (bookmarks, notes, highlights, labels)
- ✅ Phase 6: Real-time sync via Laravel Reverb (WebSocket)
- ✅ Phase 7-8: Reading plans, devotionals, song books
- ✅ Phase 9: Modern UI/UX API support (VOTD, share, history)
- ✅ Phase 10: Testing & QA (Feature + Unit tests)
- ✅ Phase 11: DevOps (Docker, GitHub Actions CI/CD, Horizon, scheduler)
- ✅ Phase 12: Documentation (OpenAPI 3.0.3, CONTRIBUTING.md, MIGRATION.md)
- All 85 issues on androidbible tracker closed

**New Issues Created (Phase 13+):**
- See GitHub issues for androidbible-api repo

**Next Steps:**
1. Implement SWORD module management API (download, install, catalog)
2. Add commentary controller (CommentaryController)
3. Add dictionary/Strong's endpoints
4. Expand sync to CRDT with vector clocks
5. Add bookmark folders
6. Add pins (quick-access verses)
7. Add Inertia/React web frontend
8. Add audio Bible endpoints

---

## 👤 User Preferences

### Project-Specific
- **Tech Stack:** Laravel 11 + PHP 8.4 + PostgreSQL 16 + Redis 7 + Meilisearch + Reverb
- **Database:** PostgreSQL 16
- **Architecture:** Monolithic Laravel (API + web in single repo), offline-first mobile
- **Auth:** Sanctum (token-based), Socialite (Google/Apple)
- **Queue:** Laravel Horizon
- **Search:** Meilisearch via Laravel Scout

### Coding Style
- **Backend:** PSR-12, Laravel conventions, Form Request validation, thin controllers
- **Commits:** Conventional commits with "Closes #N"
- **Testing:** Feature tests + Unit tests, PHPUnit
- **API:** RESTful, versioned under `/api/v1/`

### Git Workflow
- **Branch:** main
- **Commits:** Conventional commits (`feat:`, `fix:`, `docs:`, `test:`)
- **Auto-push:** After every commit
- **CI:** GitHub Actions (tests, Pint linting)

---

## 📁 Project File Map

### Key Directories
```
androidbible-api/
├── .github/
│   ├── instructions/memory.instruction.md  ← THIS FILE
│   ├── ISSUE_TEMPLATE/
│   └── workflows/ci.yml
├── agents/                  ← AI automation templates
├── app/
│   ├── Console/Commands/    ← Artisan commands (MigrateLegacyData, etc.)
│   ├── Http/Controllers/    ← All controllers
│   ├── Models/              ← Eloquent models
│   └── Services/            ← Business logic services
├── database/
│   ├── migrations/          ← All 12 migrations
│   └── seeders/
├── docs/
│   ├── PROJECT_DOCUMENTATION.md
│   ├── openapi.yaml         ← OpenAPI 3.0.3 spec
│   └── MIGRATION.md
├── routes/
│   ├── api.php              ← All API routes
│   └── channels.php         ← WebSocket channels
└── tests/
    ├── Feature/             ← Feature tests
    └── Unit/                ← Unit tests
```

### Feature → File Mapping
- **Auth:** `AuthController.php`, `SocialAuthController.php`
- **Bible Content:** `BibleController.php`, `VerseController.php`
- **Markers:** `MarkerController.php`, `LabelController.php`
- **Sync:** `SyncController.php`, `SyncService.php`
- **Reading Plans:** `ReadingPlanController.php`
- **Songs:** `SongController.php`
- **Devotionals:** `DevotionalController.php`
- **Push:** `PushNotificationController.php`
- **VOTD:** `VerseOfTheDayController.php`
- **History:** `ReadingHistoryController.php`

---

## 💭 Recent Decisions & Context

### 2026-03-12

#### Project Architecture Decision
**Decision:** Monolithic Laravel (API + future web frontend in same repo)
**Rationale:** Simpler deployment, shared models/services, Laravel Inertia can be added later

#### Feature Gap Analysis vs PocketSword
**Missing from API vs PocketSword inspiration:**
1. SWORD module management (download/install/catalog via CrossWire)
2. Commentary API (separate from Bible verses)
3. Dictionary/Strong's numbers API
4. Bookmark folders (hierarchical)
5. Pins (quick-access verses, different from bookmarks)
6. CRDT vector clock sync (currently event sourcing only)
7. Web frontend (Inertia.js + React)
8. Audio Bible endpoints
9. Verse image generation
10. Rich text notes (currently plain text)
11. Partial verse highlighting (character offsets)
12. Module source management
13. Reading history with duration tracking
14. Data export (DOCX/PDF)

---

## 🧩 Patterns & Architecture

### ARI Encoding
```
ari = (bookId << 16) | (chapter << 8) | verse
```
Used throughout for verse references.

### Sync Protocol
- Event sourcing via `sync_events` table
- Version vectors for conflict resolution
- WebSocket (Reverb) for real-time push
- Push notification fallback (FCM/APNs)
- Offline mutation queue with retry

### API Response Format
All API responses follow Laravel Resource pattern:
```json
{ "data": { ... } }
// or for lists:
{ "data": [ ... ], "meta": { "total": N, "current_page": N } }
```

### Marker Kinds
- 0 = Bookmark
- 1 = Note
- 2 = Highlight

---

## 🔧 Things to Remember

- **GitHub Repo:** https://github.com/maxymurm/androidbible-api
- **Project Board:** To be created (see agents/create_board.ps1)
- **Laravel Herd:** Available locally on Windows (PHP 8.4.16)
- **Docker:** `docker-compose up -d` starts all 8 services
- **Tests:** `php artisan test`
- **Push:** `git push origin main`
- **Issue tracker:** Issues created on this repo (androidbible-api)

---

## 📊 Project Statistics

- **Total Issues Created:** See GitHub
- **Phases Completed:** 12 of 12 original + new enhancement phases
- **Test Coverage:** 40+ tests
- **API Endpoints:** 50+ routes
- **Models:** 18
- **Migrations:** 12
