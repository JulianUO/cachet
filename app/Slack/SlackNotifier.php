<?php

namespace App\Slack;

use Cachet\Enums\ComponentStatusEnum;
use Cachet\Models\Component;
use Cachet\Models\Incident;
use Cachet\Models\Schedule;
use Cachet\Models\Update;
use Throwable;

class SlackNotifier
{
    public function __construct(
        private SlackClient $client,
        private SlackMessageBuilder $messages,
    ) {}

    public function incidentCreated(Incident $incident): void
    {
        if (! SlackNotifications::enabled() || ! SlackNotifications::shouldNotifyIncidents()) {
            return;
        }

        if (! $incident->notifications) {
            return;
        }

        if (! $incident->isPublished()) {
            return;
        }

        $this->deliver('incident_created', $this->messages->incidentCreated($incident->loadMissing('components')));
    }

    public function incidentUpdated(Incident $incident, ?Update $update = null): void
    {
        if (! SlackNotifications::enabled() || ! SlackNotifications::shouldNotifyIncidents()) {
            return;
        }

        if (! $incident->notifications) {
            return;
        }

        if (! $incident->isPublished()) {
            return;
        }

        $this->deliver(
            'incident_updated',
            $this->messages->incidentUpdated($incident->loadMissing('components'), $update),
        );
    }

    public function componentStatusChanged(
        Component $component,
        ?ComponentStatusEnum $oldStatus,
        ComponentStatusEnum $newStatus,
    ): void {
        if (! SlackNotifications::enabled() || ! SlackNotifications::shouldNotifyComponentStatus()) {
            return;
        }

        if ($oldStatus === $newStatus) {
            return;
        }

        $this->deliver(
            'component_status_changed',
            $this->messages->componentStatusChanged($component, $oldStatus, $newStatus),
        );
    }

    public function scheduleCreated(Schedule $schedule): void
    {
        if (! SlackNotifications::enabled() || ! SlackNotifications::shouldNotifySchedules()) {
            return;
        }

        if (! $schedule->notifications) {
            return;
        }

        if (! $schedule->isPublished()) {
            return;
        }

        $this->deliver('schedule_created', $this->messages->scheduleCreated($schedule->loadMissing('components')));
    }

    public function scheduleUpdated(Schedule $schedule, ?Update $update = null, ?string $reason = null): void
    {
        if (! SlackNotifications::enabled() || ! SlackNotifications::shouldNotifySchedules()) {
            return;
        }

        if (! $schedule->notifications) {
            return;
        }

        if (! $schedule->isPublished()) {
            return;
        }

        $this->deliver(
            'schedule_updated',
            $this->messages->scheduleUpdated($schedule->loadMissing('components'), $update, $reason),
        );
    }

    /**
     * @param  array{text: string, blocks?: list<array<string, mixed>>}  $message
     */
    private function deliver(string $event, array $message): void
    {
        try {
            $results = $this->client->send($message);
            SlackDeliveryLog::success($event, [
                'methods' => array_column($results, 'method'),
            ]);
        } catch (Throwable $exception) {
            SlackDeliveryLog::failure($event, $exception->getMessage());
        }
    }
}
