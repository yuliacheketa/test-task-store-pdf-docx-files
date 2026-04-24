<?php

namespace Tests\Feature;

use App\Models\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_delete_removes_file_and_record(): void
    {
        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->deleteJson(route('files.destroy', $record))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('uploaded_files', ['id' => $record->id]);
        Storage::assertMissing($record->path);
    }

    public function test_manual_delete_publishes_to_rabbitmq_with_manual_reason(): void
    {
        $record = UploadedFile::factory()->create(['original_name' => 'invoice.pdf']);
        Storage::put($record->path, 'content');

        $this->deleteJson(route('files.destroy', $record))->assertOk();

        $this->rabbitmq->assertPublished('file.deleted', function (array $payload) {
            return ($payload['reason'] ?? null) === 'manual'
                && ($payload['filename'] ?? null) === 'invoice.pdf';
        });
    }

    public function test_manual_delete_publishes_exactly_one_message(): void
    {
        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->deleteJson(route('files.destroy', $record))->assertOk();

        $this->rabbitmq->assertPublishedCount(1);
    }

    public function test_delete_of_nonexistent_file_returns_404(): void
    {
        $this->deleteJson('/files/99999')->assertNotFound();
    }
}

