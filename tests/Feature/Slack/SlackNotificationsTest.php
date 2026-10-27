<?php

use App\Filament\Pages\Integrations\ManageSlackNotifications;
use App\Models\User;
use App\Settings\SlackNotificationSettings;
use App\Slack\SlackDeliveryLog;
use App\Slack\SlackNotifications;
use App\Slack\SlackNotifier;
use Cachet\Actions\Component\ChangeComponentStatus;
use Cachet\Actions\Incident\CreateIncident;
use Cachet\Actions\Schedule\CreateSchedule;
use Cachet\Actions\Update\CreateUpdate;
use Cachet\Data\Requests\Incident\CreateIncidentRequestData;
use Cachet\Data\Requests\IncidentUpdate\CreateIncidentUpdateRequestData;
use Cachet\Data\Requests\Schedule\CreateScheduleRequestData;
use Cachet\Enums\ComponentStatusEnum;
use Cachet\Enums\ComponentStatusSourceEnum;
use Cachet\Enums\IncidentStatusEnum;
use Cachet\Models\Component;
use Cachet\Models\Incident;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $settings = app(SlackNotificationSettings::class);
    $settings->enabled = true;
    $settings->webhook_url = 'https://hooks.slack.com/services/T000/B000/TEST';
    $settings->bot_token = null;
    $settings->channel = null;
    $settings->use_bot_when_webhook_set = false;
    $settings->notify_incidents = true;
    $settings->notify_component_status = true;
    $settings->notify_schedules = true;
    $settings->username = 'Cachet';
    $settings->icon_emoji = ':traffic_light:';
    $settings->save();
});

function fakeSuccessfulSlackDelivery(): void
{
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
        'slack.com/api/*' => Http::response(['ok' => true], 200),
    ]);
}

it('reports ready when webhook is configured in settings', function () {
    expect(SlackNotifications::enabled())->toBeTrue()
        ->and(SlackNotifications::deliveryMethods())->toBe(['incoming_webhook']);
});

it('posts to Slack when a notify-enabled incident is created', function () {
    fakeSuccessfulSlackDelivery();

    $component = Component::factory()->create();

    app(CreateIncident::class)->handle(CreateIncidentRequestData::from([
        'name' => 'API outage',
        'message' => 'Investigating',
        'status' => IncidentStatusEnum::investigating,
        'visible' => true,
        'notifications' => true,
        'components' => [
            ['id' => $component->id, 'status' => ComponentStatusEnum::major_outage],
        ],
    ]));

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'hooks.slack.com')
            && str_contains($request->body(), 'API outage')
            && str_contains($request->body(), 'New incident');
    });
});

it('does not post for incidents with notifications disabled', function () {
    fakeSuccessfulSlackDelivery();

    app(CreateIncident::class)->handle(CreateIncidentRequestData::from([
        'name' => 'Silent incident',
        'message' => 'Internal only notify off',
        'status' => IncidentStatusEnum::investigating,
        'visible' => true,
        'notifications' => false,
    ]));

    Http::assertNothingSent();
});

it('posts when an incident update is created', function () {
    fakeSuccessfulSlackDelivery();

    $incident = Incident::factory()->create([
        'name' => 'Ongoing issue',
        'notifications' => true,
        'status' => IncidentStatusEnum::identified,
    ]);

    app(CreateUpdate::class)->handle($incident, CreateIncidentUpdateRequestData::from([
        'status' => IncidentStatusEnum::fixed,
        'message' => 'Resolved and monitoring.',
    ]));

    Http::assertSent(function ($request) {
        return str_contains($request->body(), 'Ongoing issue')
            && str_contains($request->body(), 'Incident updated');
    });
});

it('posts when a component status changes', function () {
    fakeSuccessfulSlackDelivery();

    $component = Component::factory()->create([
        'name' => 'Checkout',
        'status' => ComponentStatusEnum::operational,
    ]);

    app(ChangeComponentStatus::class)->handle(
        $component,
        ComponentStatusEnum::partial_outage,
        ComponentStatusSourceEnum::Manual,
    );

    Http::assertSent(function ($request) {
        return str_contains($request->body(), 'Checkout')
            && str_contains($request->body(), 'Component status changed');
    });
});

it('posts when maintenance is scheduled', function () {
    fakeSuccessfulSlackDelivery();

    $component = Component::factory()->create(['name' => 'Database']);

    app(CreateSchedule::class)->handle(CreateScheduleRequestData::from([
        'name' => 'DB upgrade',
        'message' => 'Nightly window',
        'scheduled_at' => now()->addDay(),
        'completed_at' => now()->addDay()->addHour(),
        'notifications' => true,
        'components' => [
            ['id' => $component->id, 'status' => ComponentStatusEnum::under_maintenance],
        ],
    ]));

    Http::assertSent(function ($request) {
        return str_contains($request->body(), 'DB upgrade')
            && str_contains($request->body(), 'Maintenance scheduled');
    });
});

it('posts via bot token when webhook is not set in settings', function () {
    $settings = app(SlackNotificationSettings::class);
    $settings->webhook_url = null;
    $settings->bot_token = 'xoxb-test-token';
    $settings->channel = 'C123';
    $settings->save();

    fakeSuccessfulSlackDelivery();

    $component = Component::factory()->create([
        'status' => ComponentStatusEnum::operational,
    ]);

    app(ChangeComponentStatus::class)->handle(
        $component,
        ComponentStatusEnum::major_outage,
        ComponentStatusSourceEnum::Manual,
    );

    Http::assertSent(function ($request) {
        return $request->url() === 'https://slack.com/api/chat.postMessage'
            && $request->hasHeader('Authorization', 'Bearer xoxb-test-token')
            && $request['channel'] === 'C123';
    });
});

it('records delivery failures without leaking secrets', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('invalid_token', 403),
    ]);

    $component = Component::factory()->create([
        'status' => ComponentStatusEnum::operational,
    ]);

    app(SlackNotifier::class)->componentStatusChanged(
        $component,
        ComponentStatusEnum::operational,
        ComponentStatusEnum::partial_outage,
    );

    $failure = SlackDeliveryLog::lastFailure();

    expect($failure)->not->toBeNull()
        ->and($failure['event'])->toBe('component_status_changed')
        ->and($failure['message'])->toContain('Slack webhook rejected')
        ->and($failure['message'])->not->toContain('hooks.slack.com/services/T000');
});

it('allows admins to save Slack settings from the dashboard', function () {
    $user = User::forceCreate([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ManageSlackNotifications::class)
        ->fillForm([
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/NEW/WEBHOOK/URL',
            'bot_token' => '',
            'channel' => '#ops',
            'use_bot_when_webhook_set' => false,
            'username' => 'Cachet Status',
            'icon_emoji' => ':bell:',
            'notify_incidents' => true,
            'notify_component_status' => false,
            'notify_schedules' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(SlackNotificationSettings::class)->refresh();

    expect($settings->enabled)->toBeTrue()
        ->and($settings->webhook_url)->toBe('https://hooks.slack.com/services/NEW/WEBHOOK/URL')
        ->and($settings->channel)->toBe('#ops')
        ->and($settings->username)->toBe('Cachet Status')
        ->and($settings->notify_component_status)->toBeFalse()
        ->and($settings->notify_schedules)->toBeTrue();
});

it('keeps existing secrets when secret fields are left blank', function () {
    $user = User::forceCreate([
        'name' => 'Admin',
        'email' => 'keep@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ManageSlackNotifications::class)
        ->fillForm([
            'enabled' => true,
            'webhook_url' => '',
            'bot_token' => '',
            'channel' => '#status',
            'username' => 'Cachet',
            'icon_emoji' => ':traffic_light:',
            'notify_incidents' => true,
            'notify_component_status' => true,
            'notify_schedules' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(SlackNotificationSettings::class)->refresh()->webhook_url)
        ->toBe('https://hooks.slack.com/services/T000/B000/TEST');
});

it('shows the Slack integrations page with editable fields', function () {
    $user = User::forceCreate([
        'name' => 'Admin',
        'email' => 'admin2@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($user)
        ->get(ManageSlackNotifications::getUrl())
        ->assertOk()
        ->assertSee('Native Slack notifications')
        ->assertSee('Webhook URL')
        ->assertSee('Bot user OAuth token')
        ->assertSee('Channel')
        ->assertSee('Incidents (create / update)')
        ->assertSee('Component status changes')
        ->assertSee('Maintenance / schedules')
        ->assertSee('/slack.rss');
});

it('keeps the slack rss feed available as a fallback', function () {
    $this->get(route('cachet.slack-rss'))->assertOk();
});
