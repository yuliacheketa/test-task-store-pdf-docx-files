<?php

namespace Tests\Feature;

use App\Events\FileDeleted;
use App\Models\UploadedFile;
use App\Services\FileStorageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageServiceDeleteTest extends TestCase
{
    use RefreshDatabase;

    private FileStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FileStorageService::class);
    }

    public function test_delete_removes_file_from_disk(): void
    {
        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'fake content');
        Storage::assertExists($record->path);

        $this->service->delete($record, 'manual');

        Storage::assertMissing($record->path);
    }

    public function test_delete_soft_deletes_the_db_record(): void
    {
        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        $this->assertSoftDeleted('uploaded_files', ['id' => $record->id]);
    }

    public function test_soft_deleted_record_still_exists_in_db_with_deleted_at_set(): void
    {
        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        $found = UploadedFile::withTrashed()->find($record->id);
        $this->assertNotNull($found);
        $this->assertNotNull($found->deleted_at);
    }

    public function test_delete_dispatches_file_deleted_event(): void
    {
        Event::fake();

        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        Event::assertDispatched(FileDeleted::class);
    }

    public function test_event_contains_correct_filename(): void
    {
        Event::fake();

        $record = UploadedFile::factory()->create(['original_name' => 'contract.pdf']);
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        Event::assertDispatched(FileDeleted::class, fn (FileDeleted $e) => $e->originalName === 'contract.pdf');
    }

    public function test_event_contains_correct_size(): void
    {
        Event::fake();

        $record = UploadedFile::factory()->create(['size' => 204800]);
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        Event::assertDispatched(FileDeleted::class, fn (FileDeleted $e) => $e->size === 204800);
    }

    public function test_event_reason_is_manual_when_called_with_manual(): void
    {
        Event::fake();

        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        Event::assertDispatched(FileDeleted::class, fn (FileDeleted $e) => $e->reason === 'manual');
    }

    public function test_event_reason_is_expired_when_called_with_expired(): void
    {
        Event::fake();

        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'expired');

        Event::assertDispatched(FileDeleted::class, fn (FileDeleted $e) => $e->reason === 'expired');
    }

    public function test_event_deleted_at_is_valid_iso8601(): void
    {
        Event::fake();

        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        Event::assertDispatched(FileDeleted::class, function (FileDeleted $e): bool {
            try {
                Carbon::parse($e->deletedAt);

                return true;
            } catch (\Exception) {
                return false;
            }
        });
    }

    public function test_delete_dispatches_event_exactly_once(): void
    {
        Event::fake();

        $record = UploadedFile::factory()->create();
        Storage::put($record->path, 'content');

        $this->service->delete($record, 'manual');

        Event::assertDispatchedTimes(FileDeleted::class, 1);
    }
}
