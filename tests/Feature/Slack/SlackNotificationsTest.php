<?php

use App\Filament\Pages\Integrations\ManageSlackNotifications;
use App\Models\User;
use App\Slack\SlackDeliveryLog;
use App\Slack\SlackNotifications;
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
use Cachet\Models\Schedule;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'slack_notifications.enabled' => true,
        'slack_notifications.webhook_url' => 'https://hooks.slack.com/services/T000/B000/TEST',
        'slack_notifications.bot_token' => null,
        'slack_notifications.channel' => null,
        'slack_notifications.events.incidents' => true,
        'slack_notifications.events.component_status' => true,
        'slack_notifications.events.schedules' => true,
    ]);
});

function fakeSuccessfulSlackDelivery(): void
{
    Http::fake([
        'hooks.slack.com/*' => Http::response('ok', 200),
        'slack.com/api/*' => Http::response(['ok' => true], 200),
    ]);
}

it('reports ready when webhook is configured', function () {
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

it('posts via bot token when webhook is not set', function () {
    config([
        'slack_notifications.webhook_url' => null,
        'slack_notifications.bot_token' => 'xoxb-test-token',
        'slack_notifications.channel' => 'C123',
    ]);

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

it('records delivery failures for the admin status page', function () {
    Http::fake([
        'hooks.slack.com/*' => Http::response('invalid_token', 403),
    ]);

    $component = Component::factory()->create([
        'status' => ComponentStatusEnum::operational,
    ]);

    app(\App\Slack\SlackNotifier::class)->componentStatusChanged(
        $component,
        ComponentStatusEnum::operational,
        ComponentStatusEnum::partial_outage,
    );

    $failure = SlackDeliveryLog::lastFailure();

    expect($failure)->not->toBeNull()
        ->and($failure['event'])->toBe('component_status_changed')
        ->and($failure['message'])->toContain('Slack webhook rejected');
});

it('shows Slack notifications status in the integrations page', function () {
    $user = User::forceCreate([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($user)
        ->get(ManageSlackNotifications::getUrl())
        ->assertOk()
        ->assertSee('Native Slack notifications')
        ->assertSee('Incoming webhook')
        ->assertSee('/slack.rss');
});

it('keeps the slack rss feed available as a fallback', function () {
    $this->get(route('cachet.slack-rss'))->assertOk();
});
