# Data Migration Tool

Migrates user data from the legacy Android Bible app (SQLite) to the new API backend.

## Overview

The legacy app stores data in local SQLite databases:
- `markers` table (bookmarks, notes, highlights)
- `labels` table
- `marker_label` pivot
- `progress_marks` table

## Migration Steps

### 1. Export from Legacy App

The legacy app data is in SQLite files on the device:
```
/data/data/yuku.alkitab/databases/
```

### 2. Parse Legacy Format

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Marker;
use App\Models\Label;
use App\Services\SyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateLegacyData extends Command
{
    protected $signature = 'migrate:legacy {sqlite_path} {user_email}';
    protected $description = 'Migrate legacy Android Bible SQLite data to new backend';

    public function handle(SyncService $syncService): int
    {
        $sqlitePath = $this->argument('sqlite_path');
        $userEmail = $this->argument('user_email');

        $user = User::where('email', $userEmail)->firstOrFail();

        // Connect to legacy SQLite
        config(['database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
        ]]);

        $this->info('Migrating markers...');
        $legacyMarkers = DB::connection('legacy')->table('Marker')->get();

        $bar = $this->output->createProgressBar($legacyMarkers->count());

        foreach ($legacyMarkers as $legacyMarker) {
            $marker = $user->markers()->create([
                'gid' => (string) Str::uuid(),
                'kind' => $legacyMarker->kind,
                'ari' => $legacyMarker->ari,
                'caption' => $legacyMarker->caption ?? null,
                'highlight_color' => $this->mapHighlightColor($legacyMarker->backgroundColor ?? null),
                'verse_count' => $legacyMarker->verseCount ?? 1,
                'marker_date' => $legacyMarker->createTime
                    ? date('Y-m-d H:i:s', $legacyMarker->createTime / 1000)
                    : now(),
            ]);

            $syncService->recordEvent($user, 'marker', $marker->gid, 'create', $marker->toArray(), 'migration');
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Migrating labels...');
        $legacyLabels = DB::connection('legacy')->table('Label')->get();

        foreach ($legacyLabels as $legacyLabel) {
            $label = $user->labels()->create([
                'gid' => (string) Str::uuid(),
                'title' => $legacyLabel->title,
                'background_color' => $legacyLabel->backgroundColor ?? null,
                'sort_order' => $legacyLabel->ordering ?? 0,
            ]);

            $syncService->recordEvent($user, 'label', $label->gid, 'create', $label->toArray(), 'migration');
        }

        $this->info("Migration complete. {$legacyMarkers->count()} markers, {$legacyLabels->count()} labels.");

        return Command::SUCCESS;
    }

    private function mapHighlightColor(?string $bgColor): ?int
    {
        if (!$bgColor) return null;

        // Map legacy highlight colors to new integer indices
        $colorMap = [
            '#FFFF00' => 1, // Yellow
            '#99FF99' => 2, // Green
            '#99CCFF' => 3, // Blue
            '#FF9999' => 4, // Pink
            '#FFCC99' => 5, // Orange
            '#CC99FF' => 6, // Purple
        ];

        return $colorMap[strtoupper($bgColor)] ?? 1;
    }
}
```

### 3. Run Migration

```bash
# Upload the SQLite file to server
php artisan migrate:legacy /path/to/legacy.db user@example.com
```

### 4. Verify

After migration, verify data integrity:
```bash
php artisan tinker
>>> User::where('email', 'user@example.com')->first()->markers()->count()
>>> User::where('email', 'user@example.com')->first()->labels()->count()
```

## ARI Compatibility

The legacy app uses the same ARI encoding:
```
ari = (bookId << 16) | (chapter << 8) | verse
```

No conversion is needed for ARI values — they transfer directly.

## Notes

- Legacy `kind` values match: 0=bookmark, 1=note, 2=highlight
- GIDs are generated fresh (legacy app didn't use GIDs)
- Sync events are created for each migrated item
- Highlight colors are mapped from hex strings to integer indices
