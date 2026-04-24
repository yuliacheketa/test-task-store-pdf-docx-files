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
        $count = 0;
        UploadedFile::query()
            ->expired()
            ->orderBy('id')
            ->chunkById(100, function ($files) use (&$count): void {
                foreach ($files as $file) {
                    $this->line($file->original_name);
                    $this->fileService->delete($file, 'expired');
                    $count++;
                }
            });

        if ($count === 0) {
            $this->info('No expired files found.');

            return Command::SUCCESS;
        }

        $this->info("Deleted {$count} expired file(s).");

        return Command::SUCCESS;
    }
}
