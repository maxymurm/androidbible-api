<?php

namespace App\Services;

use App\Events\SyncEventCreated;
use App\Models\SyncEvent;
use App\Models\SyncState;
use App\Models\User;
use Illuminate\Support\Str;

class SyncService
{
    /**
     * Record a sync event for event sourcing.
     */
    public function recordEvent(
        User $user,
        string $entityType,
        string $entityGid,
        string $action,
        array $payload,
        ?string $deviceId = null,
        ?array $changedFields = null
    ): SyncEvent {
        $version = $user->getNextSyncVersion();

        $event = SyncEvent::create([
            'event_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'entity_type' => $entityType,
            'entity_gid' => $entityGid,
            'action' => $action,
            'payload' => $payload,
            'changed_fields' => $changedFields,
            'device_id' => $deviceId,
            'version' => $version,
            'event_timestamp' => now(),
        ]);

        // Broadcast via Reverb to all user's devices
        broadcast(new SyncEventCreated($event))->toOthers();

        return $event;
    }

    /**
     * Pull events since a given version.
     */
    public function pullEvents(User $user, int $sinceVersion, ?string $deviceId = null, int $limit = 100): array
    {
        $events = $user->syncEvents()
            ->afterVersion($sinceVersion)
            ->orderBy('version')
            ->limit($limit)
            ->get();

        $latestVersion = $events->isNotEmpty() ? $events->last()->version : $sinceVersion;
        $hasMore = $events->count() === $limit;

        // Update device sync state
        if ($deviceId) {
            SyncState::updateOrCreate(
                ['user_id' => $user->id, 'device_id' => $deviceId],
                ['last_synced_version' => $latestVersion, 'last_sync_at' => now()]
            );
        }

        return [
            'events' => $events,
            'latest_version' => $latestVersion,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Push events from a device, applying them to the server.
     */
    public function pushEvents(User $user, array $clientEvents, string $deviceId): array
    {
        $applied = [];
        $conflicts = [];

        foreach ($clientEvents as $clientEvent) {
            try {
                // Check for conflicts (same entity modified by another device)
                $existingEvent = $user->syncEvents()
                    ->where('entity_gid', $clientEvent['entity_gid'])
                    ->where('device_id', '!=', $deviceId)
                    ->where('action', '!=', 'delete')
                    ->orderByDesc('version')
                    ->first();

                if ($existingEvent && $clientEvent['action'] === 'update') {
                    // Field-level conflict resolution: merge non-conflicting fields
                    $serverFields = $existingEvent->changed_fields ?? [];
                    $clientFields = $clientEvent['changed_fields'] ?? [];
                    $conflictingFields = array_intersect($serverFields, $clientFields);

                    if (!empty($conflictingFields)) {
                        // Last-write-wins for conflicting fields, merge others
                        $mergedPayload = array_merge(
                            $existingEvent->payload ?? [],
                            $clientEvent['payload']
                        );
                        $clientEvent['payload'] = $mergedPayload;
                    }
                }

                $event = $this->recordEvent(
                    $user,
                    $clientEvent['entity_type'],
                    $clientEvent['entity_gid'],
                    $clientEvent['action'],
                    $clientEvent['payload'],
                    $deviceId,
                    $clientEvent['changed_fields'] ?? null
                );
                $applied[] = $event->event_id;
            } catch (\Exception $e) {
                $conflicts[] = [
                    'entity_gid' => $clientEvent['entity_gid'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        $latestVersion = $user->syncEvents()->max('version') ?? 0;

        return [
            'applied' => $applied,
            'conflicts' => $conflicts,
            'latest_version' => $latestVersion,
        ];
    }

    /**
     * Get sync status for a user.
     */
    public function getStatus(User $user): array
    {
        $latestVersion = $user->syncEvents()->max('version') ?? 0;
        $deviceStates = $user->syncStates()->get();

        return [
            'latest_version' => $latestVersion,
            'total_events' => $user->syncEvents()->count(),
            'devices' => $deviceStates,
        ];
    }
}
