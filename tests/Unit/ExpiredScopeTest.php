<?php

namespace Tests\Unit;

use App\Models\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiredScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_uploaded_25_hours_ago_is_expired(): void
    {
        UploadedFile::factory()->create(['uploaded_at' => now()->subHours(25)]);

        $this->assertCount(1, UploadedFile::expired()->get());
    }

    public function test_fresh_file_uploaded_2_hours_ago_is_not_expired(): void
    {
        UploadedFile::factory()->create(['uploaded_at' => now()->subHours(2)]);

        $this->assertCount(0, UploadedFile::expired()->get());
    }

    public function test_file_at_exactly_24h_boundary_is_expired(): void
    {
        UploadedFile::factory()->create(['uploaded_at' => now()->subHours(24)]);

        $this->assertCount(
            1,
            UploadedFile::expired()->get(),
            'Scope must use <= 24h (not <). File at exactly 24h must be expired.'
        );
    }

    public function test_file_one_second_before_24h_is_not_expired(): void
    {
        UploadedFile::factory()->create([
            'uploaded_at' => now()->subHours(24)->addSecond(),
        ]);

        $this->assertCount(
            0,
            UploadedFile::expired()->get(),
            'File one second before the 24h mark must NOT be expired.'
        );
    }

    public function test_file_one_second_after_24h_mark_is_expired(): void
    {
        UploadedFile::factory()->create([
            'uploaded_at' => now()->subHours(24)->subSecond(),
        ]);

        $this->assertCount(1, UploadedFile::expired()->get());
    }

    public function test_soft_deleted_expired_file_is_excluded_from_scope(): void
    {
        $file = UploadedFile::factory()->create(['uploaded_at' => now()->subHours(30)]);
        $file->delete();

        $this->assertCount(
            0,
            UploadedFile::expired()->get(),
            'Soft-deleted file must not appear in expired() — Eloquent WHERE deleted_at IS NULL.'
        );
    }

    public function test_scope_returns_only_expired_from_mixed_batch(): void
    {
        UploadedFile::factory()->count(3)->create(['uploaded_at' => now()->subHours(25)]);
        UploadedFile::factory()->count(2)->create(['uploaded_at' => now()->subHours(2)]);
        $softDeleted = UploadedFile::factory()->create(['uploaded_at' => now()->subHours(30)]);
        $softDeleted->delete();

        $this->assertCount(3, UploadedFile::expired()->get());
    }

    public function test_scope_returns_correct_specific_records(): void
    {
        $shouldExpire = UploadedFile::factory()->create(['uploaded_at' => now()->subHours(26)]);
        $shouldStay = UploadedFile::factory()->create(['uploaded_at' => now()->subHours(1)]);

        $ids = UploadedFile::expired()->pluck('id');

        $this->assertTrue($ids->contains($shouldExpire->id));
        $this->assertFalse($ids->contains($shouldStay->id));
    }

    public function test_human_size_formats_bytes(): void
    {
        $f = UploadedFile::factory()->make(['size' => 512]);
        $this->assertEquals('512 B', $f->human_size);
    }

    public function test_human_size_formats_kilobytes(): void
    {
        $f = UploadedFile::factory()->make(['size' => 2048]);
        $this->assertEquals('2 KB', $f->human_size);
    }

    public function test_human_size_formats_megabytes(): void
    {
        $f = UploadedFile::factory()->make(['size' => 5_242_880]);
        $this->assertEquals('5 MB', $f->human_size);
    }
}
