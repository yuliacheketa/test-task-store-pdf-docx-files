<?php

namespace Tests\Feature;

use App\Events\FileDeleted;
use App\Listeners\SendFileDeletionNotification;
use App\Mail\FileDeletedMail;
use App\Services\RabbitMQFake;
use App\Services\RabbitMQService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RabbitMQNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_publishes_correct_payload_structure(): void
    {
        $event = new FileDeleted(
            originalName: 'contract.pdf',
            reason: 'manual',
            size: 102400,
            deletedAt: now()->toIso8601String(),
        );

        $listener = new SendFileDeletionNotification($this->rabbitmq);
        $listener->handle($event);

        $this->rabbitmq->assertPublished('file.deleted', function (array $payload) {
            return isset($payload['filename'], $payload['reason'], $payload['size_bytes'], $payload['deleted_at'])
                && $payload['filename'] === 'contract.pdf'
                && $payload['reason'] === 'manual'
                && $payload['size_bytes'] === 102400;
        });
    }

    public function test_listener_sends_email_notification(): void
    {
        Mail::fake();

        $event = new FileDeleted(
            originalName: 'invoice.pdf',
            reason: 'expired',
            size: 5000,
            deletedAt: now()->toIso8601String(),
        );

        $listener = new SendFileDeletionNotification($this->rabbitmq);
        $listener->handle($event);

        Mail::assertSent(FileDeletedMail::class, function (FileDeletedMail $mail) {
            return $mail->filename === 'invoice.pdf' && $mail->reason === 'expired';
        });
    }

    public function test_rabbitmq_failure_does_not_prevent_email(): void
    {
        Mail::fake();

        $brokenRmq = new class extends RabbitMQFake
        {
            public function publish(string $event, array $payload): void
            {
                throw new \RuntimeException('RabbitMQ is down');
            }
        };

        $this->app->instance(RabbitMQService::class, $brokenRmq);

        $event = new FileDeleted(
            originalName: 'file.pdf',
            reason: 'manual',
            size: 1000,
            deletedAt: now()->toIso8601String(),
        );

        $listener = new SendFileDeletionNotification($brokenRmq);
        $listener->handle($event);

        Mail::assertSent(FileDeletedMail::class);
    }
}

