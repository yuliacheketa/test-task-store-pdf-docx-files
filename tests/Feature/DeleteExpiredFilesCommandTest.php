<?php

namespace Tests\Feature;

use App\Models\UploadedFile;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DeleteExpiredFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_calls_delete_with_expired_reason_for_each_expired_file(): void
    {
        UploadedFile::factory()->create(['uploaded_at' => now()->subHours(25)]);
        UploadedFile::factory()->create(['uploaded_at' => now()->subHours(30)]);

        $mock = Mockery::mock(FileStorageService::class);
        $mock->shouldReceive('delete')
            ->twice()
            ->withArgs(function (UploadedFile $file, string $reason): bool {
                return $reason === 'expired';
            });

        $this->app->instance(FileStorageService::class, $mock);

        $this->artisan('app:delete-expired-files')->assertSuccessful();
    }

    public function test_command_does_not_call_delete_on_fresh_files(): void
    {
        UploadedFile::factory()->count(3)->create(['uploaded_at' => now()->subHours(2)]);

        $mock = Mockery::mock(FileStorageService::class);
        $mock->shouldNotReceive('delete');
        $this->app->instance(FileStorageService::class, $mock);

        $this->artisan('app:delete-expired-files')
            ->assertSuccessful()
            ->expectsOutputToContain('No expired files found');
    }

    public function test_command_does_not_call_delete_on_already_soft_deleted_files(): void
    {
        $file = UploadedFile::factory()->create(['uploaded_at' => now()->subHours(30)]);
        $file->delete();

        $mock = Mockery::mock(FileStorageService::class);
        $mock->shouldNotReceive('delete');
        $this->app->instance(FileStorageService::class, $mock);

        $this->artisan('app:delete-expired-files')
            ->assertSuccessful()
            ->expectsOutputToContain('No expired files found');
    }

    public function test_command_deletes_file_at_exactly_24h_boundary(): void
    {
        $file = UploadedFile::factory()->create(['uploaded_at' => now()->subHours(24)]);

        $mock = Mockery::mock(FileStorageService::class);
        $mock->shouldReceive('delete')->once()
            ->withArgs(fn (UploadedFile $f, string $r) => $f->id === $file->id && $r === 'expired');
        $this->app->instance(FileStorageService::class, $mock);

        $this->artisan('app:delete-expired-files')->assertSuccessful();
    }

    public function test_command_does_not_delete_file_one_second_before_24h(): void
    {
        UploadedFile::factory()->create([
            'uploaded_at' => now()->subHours(24)->addSecond(),
        ]);

        $mock = Mockery::mock(FileStorageService::class);
        $mock->shouldNotReceive('delete');
        $this->app->instance(FileStorageService::class, $mock);

        $this->artisan('app:delete-expired-files')
            ->assertSuccessful()
            ->expectsOutputToContain('No expired files found');
    }

    public function test_command_handles_mixed_batch_and_calls_delete_exactly_3_times(): void
    {
        UploadedFile::factory()->count(3)->create(['uploaded_at' => now()->subHours(25)]);
        UploadedFile::factory()->count(2)->create(['uploaded_at' => now()->subHours(2)]);
        $gone = UploadedFile::factory()->create(['uploaded_at' => now()->subHours(30)]);
        $gone->delete();

        $mock = Mockery::mock(FileStorageService::class);
        $mock->shouldReceive('delete')->times(3)
            ->withArgs(fn (UploadedFile $f, string $r) => $r === 'expired');
        $this->app->instance(FileStorageService::class, $mock);

        $this->artisan('app:delete-expired-files')
            ->assertSuccessful()
            ->expectsOutputToContain('Deleted 3');
    }

    public function test_command_publishes_rabbitmq_message_with_expired_reason(): void
    {
        UploadedFile::factory()->create([
            'uploaded_at' => now()->subHours(25),
            'original_name' => 'old-report.pdf',
            'path' => 'uploads/old-report.pdf',
        ]);

        $this->artisan('app:delete-expired-files');

        $this->rabbitmq->assertPublished('file.deleted', function (array $payload): bool {
            return $payload['reason'] === 'expired' && $payload['filename'] === 'old-report.pdf';
        });
    }

    public function test_command_publishes_exactly_n_messages_for_n_expired_files(): void
    {
        UploadedFile::factory()->count(3)->create(['uploaded_at' => now()->subHours(25)]);

        $this->artisan('app:delete-expired-files');

        $this->rabbitmq->assertPublishedCount(3);
    }

    public function test_command_publishes_zero_messages_when_no_expired_files(): void
    {
        UploadedFile::factory()->count(2)->create(['uploaded_at' => now()->subHours(2)]);

        $this->artisan('app:delete-expired-files');

        $this->rabbitmq->assertPublishedCount(0);
    }

    public function test_command_outputs_filename_of_each_deleted_file(): void
    {
        UploadedFile::factory()->create([
            'uploaded_at' => now()->subHours(25),
            'original_name' => 'quarterly-report.pdf',
            'path' => 'uploads/quarterly-report.pdf',
        ]);

        $this->artisan('app:delete-expired-files')
            ->assertSuccessful()
            ->expectsOutputToContain('quarterly-report.pdf');
    }

    public function test_command_returns_success_exit_code_when_nothing_to_delete(): void
    {
        $this->artisan('app:delete-expired-files')->assertSuccessful();
    }
}
