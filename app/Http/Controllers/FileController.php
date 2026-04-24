<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadFileRequest;
use App\Models\UploadedFile;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FileController extends Controller
{
    public function __construct(private FileStorageService $fileService) {}

    public function index(): View
    {
        $files = UploadedFile::query()->orderByDesc('uploaded_at')->paginate(20);

        return view('files.index', compact('files'));
    }

    public function upload(UploadFileRequest $request): JsonResponse
    {
        try {
            $file = $this->fileService->store($request->file('file'));

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'size' => $file->human_size,
                    'mime_type' => $file->mime_type,
                    'uploaded_at' => $file->uploaded_at?->format('d M, H:i'),
                    'expires_at' => $file->expires_at?->format('d M, H:i'),
                    'expires_in_minutes' => now()->diffInMinutes($file->expires_at, false),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Upload failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Upload failed.'], 500);
        }
    }

    public function destroy(UploadedFile $file): JsonResponse
    {
        try {
            $this->fileService->delete($file, 'manual');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Delete failed.'], 500);
        }
    }
}
