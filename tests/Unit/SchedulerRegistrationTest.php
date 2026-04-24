<?php

namespace Tests\Unit;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchedulerRegistrationTest extends TestCase
{
    public function test_delete_expired_files_is_registered_in_scheduler(): void
    {
        Artisan::call('list');

        $events = app(Schedule::class)->events();

        $found = collect($events)->first(
            fn ($e) => str_contains($e->command ?? '', 'delete-expired-files')
        );

        $this->assertNotNull(
            $found,
            'app:delete-expired-files is not registered in bootstrap/app.php withSchedule()'
        );
    }

    public function test_delete_expired_files_runs_every_five_minutes(): void
    {
        Artisan::call('list');

        $events = app(Schedule::class)->events();

        $event = collect($events)->first(
            fn ($e) => str_contains($e->command ?? '', 'delete-expired-files')
        );

        $this->assertNotNull($event, 'Scheduled event not found');

        $this->assertEquals(
            '*/5 * * * *',
            $event->expression,
            'Command must be scheduled with everyFiveMinutes() — cron expression should be */5 * * * *'
        );
    }

    public function test_delete_expired_files_has_overlap_protection(): void
    {
        Artisan::call('list');

        $event = collect(app(Schedule::class)->events())->first(
            fn ($e) => str_contains($e->command ?? '', 'delete-expired-files')
        );

        $this->assertNotNull($event, 'Scheduled event not found');
        $this->assertTrue($event->withoutOverlapping, 'Command must use withoutOverlapping()');
    }

    public function test_delete_expired_files_has_exactly_one_scheduled_entry(): void
    {
        Artisan::call('list');

        $count = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains($e->command ?? '', 'delete-expired-files'))
            ->count();

        $this->assertEquals(1, $count, 'Expected exactly 1 scheduled entry — found '.$count);
    }
}
