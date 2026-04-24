<?php

namespace Tests\Unit;

use App\Services\RabbitMQFake;
use PHPUnit\Framework\TestCase;

class RabbitMQFakeTest extends TestCase
{
    public function test_records_published_messages(): void
    {
        $fake = new RabbitMQFake;
        $fake->publish('file.deleted', ['filename' => 'a.pdf']);
        $fake->publish('file.deleted', ['filename' => 'b.pdf']);

        $fake->assertPublishedCount(2);
    }

    public function test_assert_published_passes_when_event_exists(): void
    {
        $fake = new RabbitMQFake;
        $fake->publish('file.deleted', ['reason' => 'manual']);

        $fake->assertPublished('file.deleted');
        $fake->assertPublished('file.deleted', fn (array $p) => ($p['reason'] ?? null) === 'manual');
    }

    public function test_assert_not_published_passes_when_empty(): void
    {
        $fake = new RabbitMQFake;
        $fake->assertNotPublished('file.deleted');
    }
}

