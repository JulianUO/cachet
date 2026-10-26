<?php

use Cachet\Enums\ComponentStatusEnum;
use Cachet\Enums\ComponentStatusSourceEnum;
use Cachet\Enums\IncidentStatusEnum;
use Cachet\Models\Component;
use Cachet\Models\ComponentGroup;
use Cachet\Models\ComponentStatusChange;
use Cachet\Models\Incident;
use Cachet\Models\Schedule;
use Cachet\Settings\AppSettings;
use Illuminate\Support\Carbon;

it('renders Atlassian-style system status badges, mobile bars, and axis labels', function () {
    Carbon::setTestNow('2026-09-30 12:00:00');

    $settings = app(AppSettings::class);
    $settings->display_system_status = true;
    $settings->save();

    $group = ComponentGroup::factory()->create(['name' => 'Payments']);
    Component::factory()->create([
        'name' => 'Online Payments',
        'component_group_id' => $group->id,
        'status' => ComponentStatusEnum::operational,
        'created_at' => now()->subYear(),
    ]);

    $page = $this->get(route('cachet.status-page'))
        ->assertOk()
        ->assertSee('data-component="system-status"', escape: false)
        ->assertSee('data-slot="status-badge"', escape: false)
        ->assertSee('Operational')
        ->assertSee('Uptime over the past 90 days')
        ->assertSee('90 days ago')
        ->assertSee('Today')
        ->assertSee('data-slot="history-link"', escape: false)
        ->assertSee(route('cachet.status-page.history', absolute: false))
        ->getContent();

    expect($page)
        ->toContain('data-slot="indicators"')
        ->not->toContain('hidden h-4 gap-0.5 lg:grid')
        ->toContain('grid h-8 gap-px sm:h-4');
});

it('links day events to incident and schedule detail pages', function () {
    Carbon::setTestNow('2026-09-30 12:00:00');

    $settings = app(AppSettings::class);
    $settings->display_system_status = true;
    $settings->save();

    $component = Component::factory()->create([
        'name' => 'API',
        'status' => ComponentStatusEnum::operational,
        'created_at' => now()->subYear(),
    ]);

    $incident = Incident::factory()->create([
        'name' => 'API unavailable',
        'status' => IncidentStatusEnum::fixed,
        'occurred_at' => now()->subDays(10),
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10)->addMinutes(10),
    ]);
    $incident->components()->attach($component, [
        'component_status' => ComponentStatusEnum::major_outage,
    ]);

    $schedule = Schedule::factory()->published()->create([
        'name' => 'Database Server Upgrade',
        'scheduled_at' => now()->subDays(10)->addMinutes(2),
        'completed_at' => now()->subDays(10)->addMinutes(8),
    ]);
    $schedule->components()->attach($component, [
        'component_status' => ComponentStatusEnum::under_maintenance,
    ]);

    $this->get(route('cachet.status-page'))
        ->assertOk()
        ->assertSee('data-slot="event-link"', escape: false)
        ->assertSee(route('cachet.status-page.incident', $incident, absolute: false), escape: false)
        ->assertSee(route('cachet.status-page.schedule', $schedule, absolute: false), escape: false)
        ->assertSee('This component had a major outage.');
});

it('keeps duration-based uptime calculations from core system status', function () {
    Carbon::setTestNow('2026-09-30 12:00:00');

    $settings = app(AppSettings::class);
    $settings->display_system_status = true;
    $settings->save();

    $component = Component::factory()->create([
        'name' => 'Responses',
        'status' => ComponentStatusEnum::operational,
        'created_at' => now()->subYear(),
    ]);

    ComponentStatusChange::unguarded(fn () => $component->statusChanges()->create([
        'old_status' => ComponentStatusEnum::operational,
        'new_status' => ComponentStatusEnum::major_outage,
        'source' => ComponentStatusSourceEnum::Manual,
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]));
    ComponentStatusChange::unguarded(fn () => $component->statusChanges()->create([
        'old_status' => ComponentStatusEnum::major_outage,
        'new_status' => ComponentStatusEnum::operational,
        'source' => ComponentStatusSourceEnum::Manual,
        'created_at' => now()->subDays(10)->addMinutes(10),
        'updated_at' => now()->subDays(10)->addMinutes(10),
    ]));

    $this->get(route('cachet.status-page'))
        ->assertOk()
        ->assertSee('99.99% uptime');
});
