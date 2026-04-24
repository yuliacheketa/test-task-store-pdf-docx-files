<?php

namespace Tests\Feature;

use App\Models\UploadedFile as UploadedFileModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_file_uploads_successfully(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->postJson(route('files.upload'), ['file' => $file]);

        $response
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['file' => ['id', 'original_name', 'size', 'uploaded_at']]);

        $this->assertDatabaseCount('uploaded_files', 1);
        $this->assertDatabaseHas('uploaded_files', ['original_name' => 'document.pdf']);

        $record = UploadedFileModel::query()->first();
        Storage::assertExists($record->path);
    }

    public function test_docx_file_uploads_successfully(): void
    {
        $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $file = UploadedFile::fake()->create('report.docx', 200, $mime);

        $this->postJson(route('files.upload'), ['file' => $file])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('uploaded_files', ['original_name' => 'report.docx']);
    }

    public function test_upload_fails_when_no_file_provided(): void
    {
        $this->postJson(route('files.upload'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_file_exceeding_10mb(): void
    {
        $file = UploadedFile::fake()->create('huge.pdf', 11_000, 'application/pdf');

        $this->postJson(route('files.upload'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseCount('uploaded_files', 0);
    }

    public function test_upload_rejects_txt_file(): void
    {
        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $this->postJson(route('files.upload'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_exe_renamed_as_pdf(): void
    {
        $file = UploadedFile::fake()->create('virus.pdf', 100, 'application/x-msdownload');

        $this->postJson(route('files.upload'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_index_page_loads_and_shows_uploaded_files(): void
    {
        UploadedFileModel::factory()->count(3)->create();

        $this->get(route('files.index'))
            ->assertOk()
            ->assertViewIs('files.index')
            ->assertViewHas('files');
    }

    public function test_index_page_shows_empty_state_when_no_files(): void
    {
        $this->get(route('files.index'))
            ->assertOk()
            ->assertSee('No files uploaded yet');
    }
}

