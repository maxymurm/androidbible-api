<?php

namespace Tests\Feature;

use App\Models\Marker;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_bookmark(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/markers', [
                'kind' => Marker::KIND_BOOKMARK,
                'ari' => (1 << 16) | (1 << 8) | 1,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.kind', Marker::KIND_BOOKMARK);
    }

    public function test_can_create_note(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/markers', [
                'kind' => Marker::KIND_NOTE,
                'ari' => (1 << 16) | (1 << 8) | 1,
                'caption' => 'This is an important verse about creation.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.caption', 'This is an important verse about creation.');
    }

    public function test_can_create_highlight(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/markers', [
                'kind' => Marker::KIND_HIGHLIGHT,
                'ari' => (1 << 16) | (1 << 8) | 1,
                'highlight_color' => 1,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.highlight_color', 1);
    }

    public function test_can_list_markers(): void
    {
        $this->user->markers()->create([
            'kind' => Marker::KIND_BOOKMARK,
            'ari' => (1 << 16) | (1 << 8) | 1,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/markers')
            ->assertStatus(200);
    }

    public function test_can_filter_markers_by_kind(): void
    {
        $this->user->markers()->create(['kind' => Marker::KIND_BOOKMARK, 'ari' => 65537]);
        $this->user->markers()->create(['kind' => Marker::KIND_NOTE, 'ari' => 65538, 'caption' => 'Note']);

        $this->actingAs($this->user)
            ->getJson('/api/v1/markers?kind=0')
            ->assertStatus(200);
    }

    public function test_can_update_marker(): void
    {
        $marker = $this->user->markers()->create([
            'kind' => Marker::KIND_NOTE,
            'ari' => 65537,
            'caption' => 'Original',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/markers/{$marker->id}", ['caption' => 'Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.caption', 'Updated');
    }

    public function test_can_delete_marker(): void
    {
        $marker = $this->user->markers()->create([
            'kind' => Marker::KIND_BOOKMARK,
            'ari' => 65537,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/markers/{$marker->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('markers', ['id' => $marker->id]);
    }

    public function test_cannot_access_other_users_marker(): void
    {
        $otherUser = User::factory()->create();
        $marker = $otherUser->markers()->create([
            'kind' => Marker::KIND_BOOKMARK,
            'ari' => 65537,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/markers/{$marker->id}")
            ->assertStatus(403);
    }

    public function test_can_create_label(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/labels', [
                'title' => 'Favorites',
                'background_color' => '#FF5722',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Favorites');
    }

    public function test_can_attach_label_to_marker(): void
    {
        $marker = $this->user->markers()->create([
            'kind' => Marker::KIND_BOOKMARK,
            'ari' => 65537,
        ]);
        $label = $this->user->labels()->create(['title' => 'Study']);

        $this->actingAs($this->user)
            ->postJson("/api/v1/markers/{$marker->id}/labels/{$label->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('marker_label', [
            'marker_id' => $marker->id,
            'label_id' => $label->id,
        ]);
    }

    public function test_can_batch_create_markers(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/markers/batch', [
                'markers' => [
                    ['kind' => Marker::KIND_HIGHLIGHT, 'ari' => 65537, 'highlight_color' => 1],
                    ['kind' => Marker::KIND_HIGHLIGHT, 'ari' => 65538, 'highlight_color' => 1],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_export_markers(): void
    {
        $this->user->markers()->create(['kind' => Marker::KIND_BOOKMARK, 'ari' => 65537]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/markers/export/all')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['total', 'bookmarks', 'notes', 'highlights']]);
    }
}
