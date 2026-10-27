<?php

use Cachet\Enums\ComponentStatusEnum;
use Cachet\Enums\IncidentStatusEnum;
use Cachet\Models\Component;
use Cachet\Models\Incident;
use Cachet\Models\Schedule;
use Illuminate\Support\Carbon;

it('serves the history calendar for the current month', function () {
    Carbon::setTestNow('2026-09-15 12:00:00');

    $this->get(route('cachet.status-page.history'))
        ->assertOk()
        ->assertSee('data-component="history-calendar"', escape: false)
        ->assertSee('September 2026')
        ->assertSee('data-slot="calendar-day"', escape: false);
});

it('marks disrupted days and shows day detail for incidents and maintenance', function () {
    Carbon::setTestNow('2026-09-30 12:00:00');

    $component = Component::factory()->create([
        'status' => ComponentStatusEnum::operational,
    ]);

    $incident = Incident::factory()->create([
        'name' => 'Checkout outage',
        'status' => IncidentStatusEnum::fixed,
        'occurred_at' => Carbon::parse('2026-09-12 10:00:00'),
        'created_at' => Carbon::parse('2026-09-12 10:00:00'),
    ]);
    $incident->components()->attach($component, [
        'component_status' => ComponentStatusEnum::partial_outage,
    ]);

    $schedule = Schedule::factory()->published()->create([
        'name' => 'Nightly window',
        'scheduled_at' => Carbon::parse('2026-09-12 02:00:00'),
        'completed_at' => Carbon::parse('2026-09-12 03:00:00'),
    ]);
    $schedule->components()->attach($component, [
        'component_status' => ComponentStatusEnum::under_maintenance,
    ]);

    $this->get(route('cachet.status-page.history', [
        'month' => '2026-09',
        'day' => '2026-09-12',
    ]))
        ->assertOk()
        ->assertSee('data-date="2026-09-12"', escape: false)
        ->assertSee('data-has-events="1"', escape: false)
        ->assertSee('Checkout outage')
        ->assertSee('Nightly window')
        ->assertSee(route('cachet.status-page.incident', $incident, absolute: false), escape: false)
        ->assertSee(route('cachet.status-page.schedule', $schedule, absolute: false), escape: false);
});

it('navigates between months', function () {
    Carbon::setTestNow('2026-09-15 12:00:00');

    $this->get(route('cachet.status-page.history', ['month' => '2026-08']))
        ->assertOk()
        ->assertSee('August 2026')
        ->assertSee(route('cachet.status-page.history', ['month' => '2026-09'], absolute: false), escape: false);
});
