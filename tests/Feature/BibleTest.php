<?php

namespace Tests\Feature;

use App\Models\BibleVersion;
use App\Models\Book;
use App\Models\Verse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BibleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BibleVersion $version;
    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->version = BibleVersion::create([
            'slug' => 'kjv',
            'short_name' => 'KJV',
            'name' => 'King James Version',
            'language' => 'en',
            'language_name' => 'English',
            'is_active' => true,
            'sort_order' => 1,
            'verse_count' => 31102,
        ]);

        $this->book = Book::create([
            'bible_version_id' => $this->version->id,
            'book_id' => 1,
            'short_name' => 'Gen',
            'name' => 'Genesis',
            'chapter_count' => 50,
            'verse_count' => 1533,
            'testament' => 'OT',
            'sort_order' => 1,
        ]);

        // Create sample verses
        for ($v = 1; $v <= 5; $v++) {
            Verse::create([
                'bible_version_id' => $this->version->id,
                'book_id' => $this->book->id,
                'ari' => Verse::encodeAri(1, 1, $v),
                'chapter_num' => 1,
                'verse_num' => $v,
                'text' => "Genesis 1:{$v} text content",
                'text_formatted' => "Genesis 1:{$v} text content",
            ]);
        }
    }

    public function test_can_list_versions(): void
    {
        $this->getJson('/api/v1/versions')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_version(): void
    {
        $this->getJson('/api/v1/versions/kjv')
            ->assertStatus(200)
            ->assertJsonPath('data.slug', 'kjv');
    }

    public function test_can_list_books(): void
    {
        $this->getJson('/api/v1/versions/kjv/books')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_list_chapters(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/versions/kjv/books/{$this->book->id}/chapters")
            ->assertStatus(200);
    }

    public function test_can_get_chapter_verses(): void
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/versions/kjv/books/{$this->book->id}/chapters/1")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['verses', 'pericopes', 'footnotes', 'navigation']]);
    }

    public function test_can_get_verse_by_ari(): void
    {
        $ari = Verse::encodeAri(1, 1, 1);

        $this->actingAs($this->user)
            ->getJson("/api/v1/verses/{$ari}?version=kjv")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['verse', 'book']]);
    }

    public function test_can_get_verse_of_the_day(): void
    {
        // VOTD may return 404 if curated ARI doesn't exist in test data
        $response = $this->getJson('/api/v1/verse-of-the-day?version=kjv');
        $response->assertStatus(200);
    }

    public function test_version_filter_by_language(): void
    {
        BibleVersion::create([
            'slug' => 'rv1960',
            'short_name' => 'RV1960',
            'name' => 'Reina Valera 1960',
            'language' => 'es',
            'language_name' => 'Spanish',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->getJson('/api/v1/versions?language=en')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_ari_encoding(): void
    {
        $ari = Verse::encodeAri(1, 1, 1);
        $decoded = Verse::decodeAri($ari);

        $this->assertEquals(1, $decoded['book_id']);
        $this->assertEquals(1, $decoded['chapter']);
        $this->assertEquals(1, $decoded['verse']);
    }
}
