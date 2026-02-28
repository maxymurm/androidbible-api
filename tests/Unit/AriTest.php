<?php

namespace Tests\Unit;

use App\Models\Verse;
use PHPUnit\Framework\TestCase;

class AriTest extends TestCase
{
    public function test_encode_decode_genesis_1_1(): void
    {
        $ari = Verse::encodeAri(1, 1, 1);
        $decoded = Verse::decodeAri($ari);

        $this->assertEquals(1, $decoded['book_id']);
        $this->assertEquals(1, $decoded['chapter']);
        $this->assertEquals(1, $decoded['verse']);
    }

    public function test_encode_decode_revelation_22_21(): void
    {
        $ari = Verse::encodeAri(66, 22, 21);
        $decoded = Verse::decodeAri($ari);

        $this->assertEquals(66, $decoded['book_id']);
        $this->assertEquals(22, $decoded['chapter']);
        $this->assertEquals(21, $decoded['verse']);
    }

    public function test_encode_john_3_16(): void
    {
        $ari = Verse::encodeAri(43, 3, 16);
        $decoded = Verse::decodeAri($ari);

        $this->assertEquals(43, $decoded['book_id']);
        $this->assertEquals(3, $decoded['chapter']);
        $this->assertEquals(16, $decoded['verse']);
    }

    public function test_ari_is_deterministic(): void
    {
        $ari1 = Verse::encodeAri(19, 23, 1);
        $ari2 = Verse::encodeAri(19, 23, 1);

        $this->assertEquals($ari1, $ari2);
    }

    public function test_different_verses_produce_different_aris(): void
    {
        $ari1 = Verse::encodeAri(1, 1, 1);
        $ari2 = Verse::encodeAri(1, 1, 2);
        $ari3 = Verse::encodeAri(1, 2, 1);
        $ari4 = Verse::encodeAri(2, 1, 1);

        $this->assertNotEquals($ari1, $ari2);
        $this->assertNotEquals($ari1, $ari3);
        $this->assertNotEquals($ari1, $ari4);
    }

    public function test_ari_bit_layout(): void
    {
        // ARI = (bookId << 16) | (chapter << 8) | verse
        $ari = Verse::encodeAri(1, 1, 1);
        $this->assertEquals((1 << 16) | (1 << 8) | 1, $ari);
        $this->assertEquals(65793, $ari);
    }
}
