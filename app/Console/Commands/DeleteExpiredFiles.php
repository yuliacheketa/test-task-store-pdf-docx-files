<?php

namespace App\Console\Commands;

use App\Models\UploadedFile;
use App\Services\FileStorageService;
use Illuminate\Console\Command;

class DeleteExpiredFiles extends Command
{
    protected $signature = 'app:delete-expired-files';

    protected $description = 'Delete files that have been stored for more than 24 hours';

    public function __construct(private FileStorageService $fileService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = UploadedFile::query()->expired()->get();

        if ($expired->isEmpty()) {
            $this->info('No expired files found.');

            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($expired as $file) {
            $this->line($file->original_name);
            $this->fileService->delete($file, 'expired');
            $count++;
        }

        $this->info("Deleted {$count} expired file(s).");

        return Command::SUCCESS;
    }
}
