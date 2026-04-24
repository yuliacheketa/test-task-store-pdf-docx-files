<?php

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->safeLoad();

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'] ?? 'localhost',
    (int) ($_ENV['RABBITMQ_PORT'] ?? 5672),
    $_ENV['RABBITMQ_USER'] ?? 'guest',
    $_ENV['RABBITMQ_PASSWORD'] ?? 'guest',
    $_ENV['RABBITMQ_VHOST'] ?? '/',
);

$channel = $connection->channel();
$queue = $_ENV['RABBITMQ_QUEUE'] ?? 'file_notifications';

$channel->queue_declare($queue, false, true, false, false);

echo "Waiting for messages in [{$queue}]. Press CTRL+C to stop.\n\n";

$channel->basic_consume(
    $queue,
    '',
    false,
    true,
    false,
    false,
    function ($msg) {
        $data = json_decode($msg->body, true);
        echo "─────────────────────────────────────────\n";
        echo 'Event:      '.($data['event'] ?? '?')."\n";
        echo 'Email:      '.($data['email'] ?? '?')."\n";
        echo 'Filename:   '.($data['filename'] ?? '?')."\n";
        echo 'Reason:     '.($data['reason'] ?? '?')."\n";
        echo 'Size:       '.($data['size_bytes'] ?? '?')." bytes\n";
        echo 'Deleted at: '.($data['deleted_at'] ?? '?')."\n";
        echo 'Timestamp:  '.($data['timestamp'] ?? '?')."\n";
    }
);

while ($channel->is_consuming()) {
    $channel->wait();
}

