# Contributing to Android Bible

## Getting Started

1. Fork the repositories:
   - Backend: [androidbible-api](https://github.com/maxymurm/androidbible-api)
   - Mobile: [androidbible-kmp](https://github.com/maxymurm/androidbible-kmp)

2. Clone and set up locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/androidbible-api.git
   cd androidbible-api
   docker-compose up -d
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   ```

3. Create a feature branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Guidelines

### Backend (Laravel)
- Follow PSR-12 coding standards (enforced by Laravel Pint)
- Write feature tests for all new endpoints
- Use form request validation for complex inputs
- Always use Sanctum authentication for protected routes
- Record sync events for any user data mutations
- Use the ARI encoding for Bible references: `ari = (bookId << 16) | (chapter << 8) | verse`

### Mobile (Compose Multiplatform)
- Follow the existing module structure (domain, data, ui)
- Use Koin for dependency injection
- Use SQLDelight for local database
- Use Ktor for network calls
- Use Voyager for navigation
- All UI in Compose Multiplatform (shared Android + iOS)

### Git Conventions
- Branch naming: `feature/`, `bugfix/`, `hotfix/`
- Commit messages: Follow conventional commits (`feat:`, `fix:`, `docs:`, `test:`)
- One feature per PR
- Include tests with your PR

### ARI (Absolute Reference Integer)
The ARI is the core Bible reference encoding used throughout the project:
```
ari = (bookId << 16) | (chapter << 8) | verse

// Decode:
bookId  = (ari >> 16) & 0xFF
chapter = (ari >> 8) & 0xFF
verse   = ari & 0xFF
```

Book IDs follow the standard OSIS numbering: Genesis=1, Exodus=2, ... Revelation=66.

### Marker Kinds
- `0` = Bookmark
- `1` = Note (with caption text)
- `2` = Highlight (with color)

### Sync Protocol
All user data mutations go through the sync system:
1. Client makes CRUD API call
2. Server records a `SyncEvent` with version number
3. Server broadcasts via Reverb WebSocket
4. Other devices receive real-time update or pull on reconnect

## Running Tests

### Backend
```bash
php artisan test
php artisan test --coverage
```

### Mobile
```bash
./gradlew :composeApp:testDebugUnitTest
```

## Project Board
Track progress at: https://github.com/users/maxymurm/projects/6

## License
See [LICENSE](LICENSE) in the repository root.
