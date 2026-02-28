<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_sync_status(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/sync/status')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['latest_version', 'total_events', 'devices']]);
    }

    public function test_can_push_events(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/sync/push', [
                'events' => [
                    [
                        'entity_type' => 'marker',
                        'entity_gid' => 'test-gid-123',
                        'action' => 'create',
                        'payload' => ['kind' => 0, 'ari' => 65537],
                    ],
                ],
            ], ['X-Device-Id' => 'device-1'])
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['applied', 'conflicts', 'latest_version']]);
    }

    public function test_can_pull_events(): void
    {
        // First push some events
        $syncService = app(SyncService::class);
        $syncService->recordEvent($this->user, 'marker', 'gid-1', 'create', ['kind' => 0], 'device-1');
        $syncService->recordEvent($this->user, 'marker', 'gid-2', 'create', ['kind' => 1], 'device-1');

        $this->actingAs($this->user)
            ->getJson('/api/v1/sync/pull?since_version=0', ['X-Device-Id' => 'device-2'])
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['events', 'latest_version', 'has_more']]);
    }

    public function test_pull_respects_since_version(): void
    {
        $syncService = app(SyncService::class);
        $syncService->recordEvent($this->user, 'marker', 'gid-1', 'create', ['kind' => 0], 'device-1');
        $syncService->recordEvent($this->user, 'marker', 'gid-2', 'create', ['kind' => 1], 'device-1');

        // Pull only events after version 1
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sync/pull?since_version=1', ['X-Device-Id' => 'device-2']);

        $response->assertStatus(200);
        $events = $response->json('data.events');
        $this->assertCount(1, $events);
    }

    public function test_sync_records_version(): void
    {
        $syncService = app(SyncService::class);
        $event1 = $syncService->recordEvent($this->user, 'marker', 'gid-1', 'create', ['kind' => 0]);
        $event2 = $syncService->recordEvent($this->user, 'marker', 'gid-2', 'create', ['kind' => 1]);

        $this->assertEquals(1, $event1->version);
        $this->assertEquals(2, $event2->version);
    }
}
