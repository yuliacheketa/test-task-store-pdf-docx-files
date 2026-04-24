<?php

namespace App\Services;

use PHPUnit\Framework\Assert;

class RabbitMQFake extends RabbitMQService
{
    private array $published = [];

    public function __construct() {}

    public function publish(string $event, array $payload): void
    {
        $this->published[] = [
            'event' => $event,
            'payload' => $payload,
        ];
    }

    public function getPublished(): array
    {
        return $this->published;
    }

    public function assertPublished(string $event, ?callable $callback = null): void
    {
        $matching = array_values(array_filter(
            $this->published,
            fn (array $m) => $m['event'] === $event,
        ));

        Assert::assertNotEmpty($matching, "Expected event [{$event}] was not published to RabbitMQ.");

        if ($callback) {
            $found = array_values(array_filter($matching, fn (array $m) => $callback($m['payload'])));
            Assert::assertNotEmpty($found, "Event [{$event}] was published but payload did not match assertion.");
        }
    }

    public function assertNotPublished(string $event): void
    {
        $matching = array_filter(
            $this->published,
            fn (array $m) => $m['event'] === $event,
        );

        Assert::assertEmpty($matching, "Unexpected event [{$event}] was published to RabbitMQ.");
    }

    public function assertPublishedCount(int $count): void
    {
        Assert::assertCount(
            $count,
            $this->published,
            "Expected {$count} RabbitMQ message(s), got ".count($this->published).".",
        );
    }

    public function __destruct() {}
}

