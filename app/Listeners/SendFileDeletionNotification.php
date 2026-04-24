<?php

namespace App\Listeners;

use App\Events\FileDeleted;
use App\Mail\FileDeletedMail;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendFileDeletionNotification
{
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
        } catch (Throwable) {
        }

        Mail::to((string) config('rabbitmq.email'))
            ->send(new FileDeletedMail(
                filename: $event->originalName,
                reason: $event->reason,
                deletedAt: $event->deletedAt,
            ));
    }
}
