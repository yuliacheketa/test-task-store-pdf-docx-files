<?php

namespace Tests;

use App\Services\RabbitMQFake;
use App\Services\RabbitMQService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    protected RabbitMQFake $rabbitmq;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rabbitmq = new RabbitMQFake;
        $this->app->instance(RabbitMQService::class, $this->rabbitmq);

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
