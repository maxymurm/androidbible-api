# Android Bible API — Project Documentation

**Repository:** https://github.com/maxymurm/androidbible-api  
**Type:** Laravel 11 REST API + WebSocket backend  
**Status:** Active development — Phases 1-12 complete, Phase 13+ in progress  
**Last Updated:** 2026-03-12  

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Technology Stack](#technology-stack)
4. [Project Structure](#project-structure)
5. [Database Schema](#database-schema)
6. [API Reference Summary](#api-reference-summary)
7. [Completed Phases (1–12)](#completed-phases)
8. [Roadmap (Phase 13+)](#roadmap)
9. [Development Setup](#development-setup)
10. [Testing](#testing)
11. [Deployment](#deployment)

---

## Overview

Android Bible API is the Laravel 11 backend for the Android Bible app — an open-source, offline-first Bible study application. It serves Bible content, user annotations (bookmarks, notes, highlights), reading plans, devotionals, songs, and real-time cross-device synchronization.

**Core values:**
- Offline-first (sync on reconnect, no data loss)
- Open standard (SWORD module compatibility)
- Privacy-respecting (user owns their data)
- Extensible (plugins, custom Bible versions)

---

## Architecture

```
┌─────────────────────────────────────────────┐
│                Laravel 11 API               │
│  ┌─────────────┐  ┌──────────────────────┐  │
│  │  REST API   │  │  Reverb WebSockets   │  │
│  │  /api/v1/   │  │  sync.* channels     │  │
│  └─────────────┘  └──────────────────────┘  │
│  ┌─────────────┐  ┌──────────────────────┐  │
│  │  Postgresql │  │     Meilisearch      │  │
│  │  (primary)  │  │     (full-text)      │  │
│  └─────────────┘  └──────────────────────┘  │
│  ┌─────────────┐  ┌──────────────────────┐  │
│  │    Redis    │  │   Horizon (queues)   │  │
│  │   (cache)   │  │   (bg processing)    │  │
│  └─────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────┘
         ↑                   ↑
    Android/iOS KMP     Web Frontend (future)
```

### Request Flow
1. HTTP request → `routes/api.php` → Controller → FormRequest validation
2. Controller calls Service (business logic)
3. Service uses Eloquent Models / Repositories
4. Response via Laravel Resources (JSON)

### Sync Flow
1. Client mutation → `POST /api/v1/sync/push` (batch of events)
2. `SyncService::processBatch()` applies events with conflict resolution
3. `SyncEvent` broadcast → `sync.{user_id}` Reverb channel
4. All other devices receive push update

---

## Technology Stack

| Component         | Technology            | Version  |
|-------------------|----------------------|---------|
| Framework         | Laravel               | 11.x    |
| Language          | PHP                   | 8.4     |
| Database          | PostgreSQL            | 16      |
| Cache             | Redis                 | 7       |
| Search            | Meilisearch           | latest  |
| WebSocket         | Laravel Reverb        | 1.x     |
| Queue Dashboard   | Laravel Horizon       | 5.x     |
| Auth              | Laravel Sanctum       | 4.x     |
| Social Auth       | Laravel Socialite     | 5.x     |
| Testing           | PHPUnit               | 11.x    |
| Container         | Docker + Compose      | -       |

---

## Project Structure

```
app/
├── Console/Commands/
│   └── MigrateLegacyData.php
├── Events/
│   └── SyncEventBroadcast.php
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── BibleController.php
│   │   ├── BookController.php
│   │   ├── ChapterController.php
│   │   ├── CommentaryController.php        (Phase 13 - planned)
│   │   ├── DevotionalController.php
│   │   ├── DictionaryController.php        (Phase 13 - planned)
│   │   ├── LabelController.php
│   │   ├── MarkerController.php
│   │   ├── ModuleController.php            (Phase 13 - planned)
│   │   ├── PinController.php               (Phase 13 - planned)
│   │   ├── PushNotificationController.php
│   │   ├── ReadingHistoryController.php
│   │   ├── ReadingPlanController.php
│   │   ├── SocialAuthController.php
│   │   ├── SongController.php
│   │   ├── SyncController.php
│   │   ├── UserController.php
│   │   └── VerseOfTheDayController.php
│   ├── Requests/
│   └── Resources/
├── Models/
│   ├── BibleVersion.php
│   ├── Book.php
│   ├── Chapter.php
│   ├── Verse.php
│   ├── User.php
│   ├── Device.php
│   ├── Marker.php
│   ├── Label.php
│   ├── Pin.php                             (Phase 13 - planned)
│   ├── BookmarkFolder.php                  (Phase 13 - planned)
│   ├── ReadingPlan.php
│   ├── Devotional.php
│   ├── Song.php
│   ├── SyncEvent.php
│   └── ReadingHistory.php
├── Services/
│   ├── SyncService.php
│   ├── ModuleService.php                   (Phase 13 - planned)
│   └── SearchService.php
database/
├── migrations/
│   ├── 2025_01_01_000001_create_users_table.php
│   ├── 2025_01_01_000002_create_devices_table.php
│   ├── 2025_01_01_000003_create_bible_versions_table.php
│   ├── 2025_01_01_000004_create_books_table.php
│   ├── 2025_01_01_000005_create_chapters_table.php
│   ├── 2025_01_01_000006_create_verses_table.php
│   ├── 2025_01_01_000007_create_markers_table.php
│   ├── 2025_01_01_000008_create_labels_table.php
│   ├── 2025_01_01_000009_create_sync_events_table.php
│   ├── 2025_01_01_000010_create_reading_plans_table.php
│   ├── 2025_01_01_000011_create_devotionals_table.php
│   └── 2025_01_01_000012_create_songs_table.php
routes/
├── api.php
└── channels.php
```

---

## Database Schema

### Core Tables

| Table           | Key Columns                                          | Purpose                        |
|-----------------|------------------------------------------------------|-------------------------------|
| users           | id, email, name, password, gid, preferences (json)   | User accounts                 |
| devices         | id, user_id, platform, push_token, last_sync_at      | Per-device sync tracking      |
| bible_versions  | id, code, name, language, sword_module               | Bible translations             |
| books           | id, version_id, book_num, name, abbreviation         | Bible books                   |
| chapters        | id, book_id, chapter_num                             | Bible chapters                |
| verses          | id, chapter_id, verse_num, text, ari                 | Bible verses                  |
| markers         | id, user_id, verse_id, kind, color, note, gid        | Bookmarks/Notes/Highlights     |
| labels          | id, user_id, name, color                             | Marker grouping labels        |
| sync_events     | id, user_id, device_id, kind, payload (json)         | Event log for sync             |
| reading_plans   | id, user_id, title, days_json, progress_json         | Reading plan tracking          |
| devotionals     | id, date, title, body, verse_ref                     | Daily devotionals              |
| songs           | id, title, lyrics, book_ref                         | Hymns/songs                   |

### Planned Tables (Phase 13+)

| Table           | Key Columns                                          | Purpose                        |
|-----------------|------------------------------------------------------|-------------------------------|
| pins            | id, user_id, verse_id, note, gid                    | Quick-access verse pins        |
| bookmark_folders| id, user_id, name, parent_id, color                 | Nested bookmark folders        |
| modules         | id, name, type, language, installed, source_url     | SWORD module registry          |
| reading_history | id, user_id, verse_id, duration_ms, started_at      | Detailed reading history       |

---

## API Reference Summary

### Authentication
```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
GET    /api/v1/auth/user
POST   /api/v1/auth/social/{provider}
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
```

### Bible Content
```
GET    /api/v1/bible/versions
GET    /api/v1/bible/versions/{version}/books
GET    /api/v1/bible/versions/{version}/books/{book}/chapters
GET    /api/v1/bible/versions/{version}/books/{book}/chapters/{chapter}/verses
GET    /api/v1/bible/verse/{ari}
GET    /api/v1/bible/verse-of-the-day
GET    /api/v1/bible/search?q={query}&version={version}
```

### Markers (Bookmarks, Notes, Highlights)
```
GET    /api/v1/markers
POST   /api/v1/markers
GET    /api/v1/markers/{gid}
PUT    /api/v1/markers/{gid}
DELETE /api/v1/markers/{gid}
GET    /api/v1/labels
POST   /api/v1/labels
PUT    /api/v1/labels/{id}
DELETE /api/v1/labels/{id}
```

### Sync
```
POST   /api/v1/sync/push
GET    /api/v1/sync/pull?since={timestamp}
GET    /api/v1/sync/status
```

### Reading Plans
```
GET    /api/v1/reading-plans
POST   /api/v1/reading-plans
GET    /api/v1/reading-plans/{id}
PUT    /api/v1/reading-plans/{id}/progress
```

### Planned Endpoints (Phase 13+)
```
GET    /api/v1/modules
GET    /api/v1/modules/{name}/commentary/{book}/{chapter}/{verse}
GET    /api/v1/modules/{name}/dictionary/{key}
GET    /api/v1/bible/strongs/{number}
GET    /api/v1/pins
POST   /api/v1/pins
DELETE /api/v1/pins/{gid}
GET    /api/v1/bookmark-folders
POST   /api/v1/bookmark-folders
```

---

## Completed Phases

### Phase 1: Foundation & Infrastructure
- Docker Compose (PHP-FPM, Nginx, PostgreSQL, Redis, Meilisearch, Reverb, Horizon)
- GitHub Actions CI/CD pipeline
- `.env` structure and secrets management

### Phase 2: Database Schema & Core Models
- 12 migrations (users through songs)
- Eloquent models with relationships
- Seeders for development data

### Phase 3: Authentication & User Management
- Sanctum token auth
- Google + Apple OAuth via Socialite
- Password reset (email tokens)
- Device registration

### Phase 4: Bible Core Features
- Bible versions API
- Books, chapters, verses endpoints
- ARI-based verse references
- Cross-reference and footnote support
- Verse of the Day endpoint

### Phase 5: Markers System
- Unified Marker model (kind: 0=bookmark, 1=note, 2=highlight)
- GID-based sync keys
- Labels/collections for grouping

### Phase 6: Real-time Sync
- SyncController + SyncService
- Event sourcing via sync_events
- Reverb WebSocket channels (`sync.{user_id}`)
- Version vector conflict resolution

### Phase 7: Reading Plans & Devotionals
- CRUD reading plans
- Progress tracking
- Devotionals (date-based)

### Phase 8: Song Books & Hymns
- Song model + CRUD API
- Book reference tagging

### Phase 9: UI/UX Support
- VOTD endpoint
- Verse sharing API
- Reading history
- Preferences (user JSON column)

### Phase 10: Testing & QA
- 40+ PHPUnit tests
- Feature + Unit test suites
- Test database seeding

### Phase 11: DevOps & Deployment
- GitHub Actions: lint + test on PR
- Docker production configuration
- Horizon queue monitoring
- Scheduled tasks (VOTD rotation, sync cleanup)

### Phase 12: Documentation
- OpenAPI 3.0.3 specification (`docs/openapi.yaml`)
- README with setup guide
- CONTRIBUTING.md
- MIGRATION.md (legacy Android migration guide)

---

## Roadmap

### Phase 13: SWORD Module System
- Module registry table
- SWORD module download + install pipeline
- CrossWire catalog API integration
- Commentary API endpoints (per verse, per module)
- Dictionary/Lexicon API endpoints
- Strong's numbers index

### Phase 14: Enhanced Annotations
- Bookmark folders (hierarchical)
- Pins model (quick-access verses)
- 6-color highlight palette (distinct color model)
- Rich-text notes (Markdown storage)
- Tags/collections system

### Phase 15: Web Frontend (Inertia.js + React)
- Inertia.js setup in Laravel
- SSR-ready React 19 components
- Bible reader page
- Markers management page
- User dashboard

### Phase 16: Advanced Search & Study Tools
- Strong's number search endpoint
- Morphology filter search
- Word concordance API
- Word study (all occurrences)
- Advanced Meilisearch query DSL

### Phase 17: Audio Bible & Media
- Audio Bible module API
- Streaming endpoints
- Verse image generation
- Media library management

### Phase 18: Statistics & Export
- Reading statistics (streaks, chapters/day)
- Data export (DOCX/PDF generation)
- Reading history detailed tracking (duration, scroll position)

---

## Development Setup

### Prerequisites
- PHP 8.4 (Laravel Herd recommended on Windows)
- Docker Desktop
- GitHub CLI (`gh`)
- Composer

### Quick Start
```bash
git clone https://github.com/maxymurm/androidbible-api.git
cd androidbible-api
cp .env.example .env
composer install
docker-compose up -d
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Services (Docker)
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`
- Meilisearch: `localhost:7700`
- Reverb: `localhost:8080`
- Horizon UI: `/horizon`

---

## Testing

```bash
# Run all tests
php artisan test

# Run specific suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage
```

---

## Deployment

### Docker (Production)
```bash
docker-compose -f docker-compose.prod.yml up -d
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan horizon:start
```

### GitHub Actions
CI pipeline runs on every PR:
1. PHP Pint lint check
2. PHPUnit test suite
3. Builds Docker image (on main branch)
