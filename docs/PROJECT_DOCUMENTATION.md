# Project Documentation  androidbible-api

**Repository:** https://github.com/maxymurm/androidbible-api  
**Type:** Laravel 11 REST API + WebSocket backend for BibleCMP  
**Reference:** goldenBowl Laravel backend (writings.gadsda.com)  
**Language:** PHP 8.4  
**Date:** 2026-03-12

---

## Project Overview

androidbible-api is the Laravel 11 backend powering the **BibleCMP** Compose Multiplatform Bible app. It provides:
- Authentication (email/password + Google/Apple OAuth via Sanctum)
- Delta sync for markers, labels, and reading progress
- Real-time broadcasting (Laravel Reverb  Pusher protocol)
- Bible content serving (versions catalog, reading plans, devotions, songs)
- Version download catalog (YES2 files)

The API design mirrors the **goldenBowl** reference implementation deployed at `https://writings.gadsda.com`.

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 |
| Language | PHP 8.4 |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis 7 |
| Search | Meilisearch + Laravel Scout |
| Real-time | Laravel Reverb (WebSocket) |
| Auth | Laravel Sanctum + Socialite |
| Queue Dashboard | Laravel Horizon |
| Testing | PHPUnit / Pest |
| CI/CD | GitHub Actions |
| Container | Docker (8 services) |
| Production | Laravel Forge + DigitalOcean/Hetzner |

---

## Phases Completed (112)

| Phase | Description | Issues |
|-------|-------------|--------|
| 1 | Foundation & Infrastructure | 9 |
| 2 | Database Schema & Core Models | 12 |
| 3 | Authentication & User Management | 6 |
| 4 | Bible Core Features (content API) | 9 |
| 5 | Markers System (CRUD API) | 9 |
| 6 | Real-time Sync (Reverb/WebSocket) | 6 |
| 7 | Reading Plans & Devotionals | 5 |
| 8 | Song Books & Hymns | 4 |
| 9 | UI/UX API Support | 9 |
| 10 | Testing & QA | 5 |
| 11 | DevOps & Deployment | 6 |
| 12 | Documentation & Launch | 5 |

---

## Phase Plan (13+)  goldenBowl Feature Alignment

### Phase 13: Sync Protocol Hardening
**Goal:** Match goldenBowl sync protocol exactly  
**Issues:** ~10

- [ ] Implement `POST /api/sync/` with delta sync + revision tracking
- [ ] Implement `SyncShadow` model for conflict detection (last-write-wins)
- [ ] Echo prevention: filter broadcasts by `device_id`
- [ ] `GET /api/sync/delta?since=N` endpoint
- [ ] `GET /api/sync/full` endpoint
- [ ] `POST /api/sync/device`  device registration
- [ ] `GET /api/sync/devices` + `DELETE /api/sync/devices/{id}`
- [ ] Sync SyncState table (revisionId, lastSyncAt per syncSetName)
- [ ] Rate limiting on sync endpoints (`throttle:sync`)
- [ ] Sync logs endpoint for debugging

### Phase 14: Real-time Broadcasting
**Goal:** Full Pusher protocol broadcasting via Reverb  
**Issues:** ~8

- [ ] `POST /api/broadcasting/auth`  Sanctum-based channel auth (NOT session)
- [ ] `MarkerCreated`, `MarkerUpdated`, `MarkerDeleted` broadcast events
- [ ] `LabelUpdated` broadcast event
- [ ] `ProgressUpdated` broadcast event
- [ ] Private channel: `private-user.{userId}`
- [ ] Reverb production config (REVERB_HOST=0.0.0.0, Nginx proxy)
- [ ] Test WebSocket broadcasting end-to-end
- [ ] Rate limiting on broadcasting auth

### Phase 15: Authentication Enhancements
**Goal:** Full goldenBowl auth feature set  
**Issues:** ~8

- [ ] Apple Sign-In: manual JWKS verification (not Socialite  Apple uses JWT)
- [ ] `POST /api/auth/forgot-password`  email reset link
- [ ] `POST /api/auth/reset-password` with token
- [ ] `DELETE /api/auth/account`  full account + data deletion
- [ ] `GET /api/user` with `sync_revision` and `last_sync_at`
- [ ] Multi-device token management
- [ ] Device type (`android | ios | web`) in device registration
- [ ] Token invalidation on account deletion

### Phase 16: Version Catalog & Downloads
**Goal:** Serve YES2 Bible version files and catalog  
**Issues:** ~8

- [ ] `GET /api/versions`  catalog of available YES2 versions
- [ ] `GET /api/versions/{id}/download`  download YES2 file
- [ ] Version metadata (locale, shortName, longName, description, fileSize)
- [ ] CDN integration for large YES2 file serving
- [ ] Bundled/internal version metadata
- [ ] Per-language version grouping
- [ ] Version changelog API
- [ ] Admin: upload new YES2 version files

### Phase 17: Reading Plans & Content
**Goal:** Comprehensive content API for plans, devotions, songs  
**Issues:** ~10

- [ ] Reading plan CRUD + progress sync
- [ ] `GET /api/reading-plans`  list all plans
- [ ] `POST /api/reading-plans/{id}/progress`  mark daily reading
- [ ] Devotional content API (daily, by date)
- [ ] Song database API (browse, search, lyrics)
- [ ] VOTD (Verse of the Day) with date-based variation
- [ ] `GET /api/health`  health check endpoint
- [ ] CORS configuration for client apps
- [ ] API versioning preparation

### Phase 18: Production Deployment
**Goal:** Production-grade deployment  
**Issues:** ~8

- [ ] Laravel Forge configuration (DigitalOcean/Hetzner)
- [ ] Production `.env` hardening (no debug, secure keys)
- [ ] Nginx reverse proxy config (PHP-FPM + Reverb WebSocket)
- [ ] SSL/TLS with Let's Encrypt
- [ ] Horizon dashboard secured (`/horizon`)
- [ ] PostgreSQL backup automation
- [ ] Zero-downtime deployment (GitHub Actions  Forge)
- [ ] Monitoring with Sentry/BugSnag

---

## API Endpoints Summary

```
POST /api/auth/login
POST /api/auth/register
POST /api/auth/oauth/{provider}   (google | apple)
POST /api/auth/logout
PUT  /api/auth/change-password
DELETE /api/auth/account
POST /api/auth/forgot-password
POST /api/auth/reset-password

GET  /api/user

GET  /api/sync/status
POST /api/sync/
GET  /api/sync/full
GET  /api/sync/delta
POST /api/sync/device
GET  /api/sync/devices
DELETE /api/sync/devices/{id}
GET  /api/sync/logs

POST /api/broadcasting/auth

GET  /api/versions
GET  /api/versions/{id}/download

GET  /api/reading-plans
POST /api/reading-plans/{id}/progress
GET  /api/devotions/today
GET  /api/songs
GET  /api/songs/{id}

GET  /api/health
```

---

## Database Schema (Key Tables)

```sql
users (id, name, email, password, sync_revision, last_sync_at)
devices (id, user_id, device_id UUID, device_name, device_type, platform_version, app_version)
markers (id, gid UUID, user_id, ari, kind, caption, verse_count, created_at, updated_at, deleted_at)
labels (id, gid UUID, user_id, title, ordering, background_color, created_at, updated_at)
marker_label (marker_id, label_id)
progress_marks (id, gid UUID, user_id, preset_id, caption, ari, updated_at)
sync_shadows (id, user_id, entity_type, entity_gid, last_revision)
sync_logs (id, user_id, event, details, created_at)
reading_plans (id, name, title, description, duration, start_time)
reading_plan_progress (id, user_id, reading_plan_id, reading_code, check_time)
songs (id, number, title, lyrics, created_at)
devotions (id, date, title, content, ari, created_at)
```

---

## Running Locally

```bash
# Start all services
docker-compose up -d

# Run migrations
php artisan migrate --seed

# Start queue
php artisan horizon

# Start WebSocket server
php artisan reverb:start

# Run tests
php artisan test

# Run linter
./vendor/bin/pint
```

---

## Related Repositories

- **KMP app:** https://github.com/maxymurm/androidbible-kmp
- **Original Android app:** https://github.com/maxymurm/androidbible
- **Reference backend:** goldenBowl (writings.gadsda.com)
