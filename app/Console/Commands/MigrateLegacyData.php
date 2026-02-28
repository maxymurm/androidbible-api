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

        if (! file_exists($sqlitePath)) {
            $this->error("SQLite file not found: {$sqlitePath}");

            return Command::FAILURE;
        }

        $user = User::where('email', $userEmail)->first();

        if (! $user) {
            $this->error("User not found: {$userEmail}");

            return Command::FAILURE;
        }

        // Connect to legacy SQLite
        config(['database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
        ]]);

        $markerCount = $this->migrateMarkers($user, $syncService);
        $labelCount = $this->migrateLabels($user, $syncService);
        $this->migrateLabelAssignments();

        $this->newLine();
        $this->info("Migration complete: {$markerCount} markers, {$labelCount} labels.");

        return Command::SUCCESS;
    }

    private function migrateMarkers(User $user, SyncService $syncService): int
    {
        $this->info('Migrating markers...');

        $legacyMarkers = DB::connection('legacy')->table('Marker')->get();
        $bar = $this->output->createProgressBar($legacyMarkers->count());

        $gidMap = [];

        foreach ($legacyMarkers as $legacy) {
            $gid = (string) Str::uuid();

            $marker = $user->markers()->create([
                'gid' => $gid,
                'kind' => $legacy->kind,
                'ari' => $legacy->ari,
                'caption' => $legacy->caption ?? null,
                'highlight_color' => $this->mapHighlightColor($legacy->backgroundColor ?? null),
                'verse_count' => $legacy->verseCount ?? 1,
                'marker_date' => isset($legacy->createTime)
                    ? date('Y-m-d H:i:s', $legacy->createTime / 1000)
                    : now(),
            ]);

            $syncService->recordEvent($user, 'marker', $marker->gid, 'create', $marker->toArray(), 'migration');

            // Map legacy _id to new marker for label pivot migration
            if (isset($legacy->_id)) {
                $gidMap[$legacy->_id] = $marker->id;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Store mapping for label assignment migration
        $this->legacyMarkerMap = $gidMap;

        return $legacyMarkers->count();
    }

    private function migrateLabels(User $user, SyncService $syncService): int
    {
        $this->info('Migrating labels...');

        $legacyLabels = DB::connection('legacy')->table('Label')->get();

        $labelMap = [];

        foreach ($legacyLabels as $legacy) {
            $label = $user->labels()->create([
                'gid' => (string) Str::uuid(),
                'title' => $legacy->title,
                'background_color' => $legacy->backgroundColor ?? null,
                'sort_order' => $legacy->ordering ?? 0,
            ]);

            $syncService->recordEvent($user, 'label', $label->gid, 'create', $label->toArray(), 'migration');

            if (isset($legacy->_id)) {
                $labelMap[$legacy->_id] = $label->id;
            }
        }

        $this->legacyLabelMap = $labelMap;

        return $legacyLabels->count();
    }

    private function migrateLabelAssignments(): void
    {
        $this->info('Migrating label assignments...');

        try {
            $pivotRows = DB::connection('legacy')->table('Marker_Label')->get();
        } catch (\Exception $e) {
            $this->warn('No Marker_Label table found, skipping assignments.');

            return;
        }

        $count = 0;

        foreach ($pivotRows as $row) {
            $markerId = $this->legacyMarkerMap[$row->marker_id] ?? null;
            $labelId = $this->legacyLabelMap[$row->label_id] ?? null;

            if ($markerId && $labelId) {
                DB::table('marker_labels')->insertOrIgnore([
                    'marker_id' => $markerId,
                    'label_id' => $labelId,
                ]);
                $count++;
            }
        }

        $this->info("  Assigned {$count} label-marker relationships.");
    }

    private function mapHighlightColor(?string $bgColor): ?int
    {
        if (! $bgColor) {
            return null;
        }

        // Map legacy ARGB integer colors to new index-based system
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

    private array $legacyMarkerMap = [];

    private array $legacyLabelMap = [];
}
