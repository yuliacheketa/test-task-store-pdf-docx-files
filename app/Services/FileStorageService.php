<?php

namespace App\Services;

use App\Events\FileDeleted;
use App\Models\UploadedFile;
use Illuminate\Http\UploadedFile as RequestUploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    public function store(RequestUploadedFile $file): UploadedFile
    {
        $original = $file->getClientOriginalName();
        $storedName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->storeAs('uploads', $storedName);
        $path = 'uploads/'.$storedName;
        $size = (int) $file->getSize();
        $mime = $file->getMimeType() ?? 'application/octet-stream';

        $model = UploadedFile::query()->create([
            'original_name' => $original,
            'stored_name' => $storedName,
            'path' => $path,
            'mime_type' => $mime,
            'size' => $size,
            'uploaded_at' => now(),
        ]);

        Log::info('File uploaded', [
            'name' => $original,
            'size' => $size,
        ]);

        return $model;
    }

    public function delete(UploadedFile $file, string $reason = 'manual'): void
    {
        $originalName = $file->original_name;
        $size = (int) $file->size;

        Storage::delete($file->path);
        $file->delete();

        $deletedAt = now()->toIso8601String();
        event(new FileDeleted($originalName, $reason, $size, $deletedAt));

        Log::info('File deleted', [
            'name' => $originalName,
            'reason' => $reason,
        ]);
    }
}
