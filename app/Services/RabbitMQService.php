<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    public function __construct()
    {
        try {
            $this->connection = new AMQPStreamConnection(
                (string) config('rabbitmq.host'),
                (int) config('rabbitmq.port'),
                (string) config('rabbitmq.user'),
                (string) config('rabbitmq.password'),
                (string) config('rabbitmq.vhost'),
            );
            $this->channel = $this->connection->channel();
            $this->channel->queue_declare((string) config('rabbitmq.queue'), false, true, false, false);
        } catch (Throwable $e) {
            Log::error('RabbitMQ connection failed', ['error' => $e->getMessage()]);
            $this->connection = null;
            $this->channel = null;
        }
    }

    public function publish(string $event, array $payload): void
    {
        try {
            if ($this->channel === null) {
                return;
            }
            $body = array_merge([
                'event' => $event,
                'email' => config('rabbitmq.email'),
                'timestamp' => now()->toIso8601String(),
            ], $payload);
            $message = new AMQPMessage(
                json_encode($body, JSON_THROW_ON_ERROR),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );
            $this->channel->basic_publish($message, '', (string) config('rabbitmq.queue'));
        } catch (Throwable $e) {
            Log::error('RabbitMQ publish failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function __destruct()
    {
        try {
            if ($this->channel !== null) {
                $this->channel->close();
            }
        } catch (Throwable) {
        }
        try {
            if ($this->connection !== null) {
                $this->connection->close();
            }
        } catch (Throwable) {
        }
    }
}
