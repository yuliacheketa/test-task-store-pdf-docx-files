<?php

namespace App\Listeners;

use App\Events\FileDeleted;
use App\Mail\FileDeletedMail;
use App\Services\RabbitMQService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendFileDeletionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function __construct(private RabbitMQService $rabbitmq) {}

    public function handle(FileDeleted $event): void
    {
        try {
            $this->rabbitmq->publish('file.deleted', [
                'filename' => $event->originalName,
                'reason' => $event->reason,
                'size_bytes' => $event->size,
                'deleted_at' => $event->deletedAt,
            ]);
        } catch (Throwable $e) {
            Log::error('RabbitMQ publish failed in notification listener', [
                'filename' => $event->originalName,
                'error' => $e->getMessage(),
            ]);
        }

        Mail::to((string) config('rabbitmq.email'))
            ->send(new FileDeletedMail(
                filename: $event->originalName,
                reason: $event->reason,
                deletedAt: $event->deletedAt,
            ));
    }
}
