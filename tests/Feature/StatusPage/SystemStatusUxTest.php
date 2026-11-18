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
        ->toContain('class="uptime-bars mt-3"')
        ->toContain('uptime-bar uptime-bar--operational')
        ->not->toContain('grid h-8 gap-px sm:h-4')
        // Mobile shows the last 30 days: 60 of the 90 days per row are flagged as older.
        ->and(substr_count($page, 'uptime-day uptime-day--older'))->toBe(120)
        ->and($page)->toContain('30 days ago');
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
        ->assertSee('data-slot="downtime-row"', escape: false)
        ->assertSee('Related');
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

it('uses the configured number of system status days for bars and labels', function () {
    Carbon::setTestNow('2026-09-30 12:00:00');

    $settings = app(AppSettings::class);
    $settings->display_system_status = true;
    $settings->system_status_days = 30;
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
        ->assertSee('Uptime over the past 30 days')
        ->assertSee('30 days ago')
        ->assertDontSee('90 days ago')
        ->getContent();

    expect(substr_count($page, 'data-component="system-status-day"'))->toBe(60)
        ->and(substr_count($page, '--uptime-days: 30'))->toBe(2)
        ->and($page)->not->toContain('uptime-day uptime-day--older');
});

it('lists downtime durations per status in the day tooltip, like Atlassian', function () {
    Carbon::setTestNow('2026-09-30 12:00:00');

    $settings = app(AppSettings::class);
    $settings->display_system_status = true;
    $settings->system_status_days = 7;
    $settings->save();

    $component = Component::factory()->create([
        'name' => 'Checkout',
        'status' => ComponentStatusEnum::operational,
        'created_at' => now()->subYear(),
    ]);

    $at = Carbon::parse('2026-09-28 10:00:00');
    foreach ([
        [ComponentStatusEnum::operational, ComponentStatusEnum::partial_outage, 0],
        [ComponentStatusEnum::partial_outage, ComponentStatusEnum::major_outage, 125],
        [ComponentStatusEnum::major_outage, ComponentStatusEnum::operational, 135],
    ] as [$old, $new, $minutes]) {
        ComponentStatusChange::unguarded(fn () => $component->statusChanges()->create([
            'old_status' => $old,
            'new_status' => $new,
            'source' => ComponentStatusSourceEnum::Manual,
            'created_at' => $at->clone()->addMinutes($minutes),
            'updated_at' => $at->clone()->addMinutes($minutes),
        ]));
    }

    $page = $this->get(route('cachet.status-page'))->assertOk()->getContent();

    expect($page)
        ->toContain('uptime-bar uptime-bar--major')
        ->toContain('No downtime recorded on this day.')
        ->and(preg_match('/Major outage<\/span>\s*<\/span>\s*<span[^>]*data-slot="duration">10m<\/span>/', $page))->toBe(1)
        ->and(preg_match('/Partial outage<\/span>\s*<\/span>\s*<span[^>]*data-slot="duration">2h 5m<\/span>/', $page))->toBe(1);
});
