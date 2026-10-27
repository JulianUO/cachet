<?php

namespace App\Listeners;

use App\Slack\SlackNotifier;
use Cachet\Enums\ScheduleStatusEnum;
use Cachet\Events\Components\ComponentStatusWasChanged;
use Cachet\Events\Incidents\IncidentCreated;
use Cachet\Events\Incidents\IncidentUpdated;
use Cachet\Models\Incident;
use Cachet\Models\Schedule;
use Cachet\Models\Update;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSlackNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function __construct(private SlackNotifier $notifier) {}

    public function handleIncidentCreated(IncidentCreated $event): void
    {
        $this->notifier->incidentCreated($event->incident);
    }

    public function handleIncidentUpdated(IncidentUpdated $event): void
    {
        // Prefer Update::created for update posts; only notify on direct incident edits
        // (name/message/visibility/etc.), not status-only sync from an update.
        $incident = $event->incident;
        $changed = array_keys($incident->getChanges());
        $statusOnly = $changed === ['status'] || $changed === ['status', 'updated_at'];

        if ($statusOnly) {
            return;
        }

        if ($changed === []) {
            return;
        }

        $this->notifier->incidentUpdated($incident);
    }

    public function handleComponentStatusChanged(ComponentStatusWasChanged $event): void
    {
        $this->notifier->componentStatusChanged(
            $event->component,
            $event->oldStatus,
            $event->newStatus,
        );
    }

    public function handleUpdateCreated(Update $update): void
    {
        $parent = $update->updateable;

        if ($parent instanceof Incident) {
            $this->notifier->incidentUpdated($parent, $update);

            return;
        }

        if ($parent instanceof Schedule) {
            $reason = $parent->status === ScheduleStatusEnum::complete ? 'completed' : 'updated';
            $this->notifier->scheduleUpdated($parent, $update, $reason);
        }
    }

    public function handleScheduleCreated(Schedule $schedule): void
    {
        $this->notifier->scheduleCreated($schedule);
    }

    public function handleScheduleUpdated(Schedule $schedule): void
    {
        if ($schedule->wasChanged('completed_at') && $schedule->status === ScheduleStatusEnum::complete) {
            $this->notifier->scheduleUpdated($schedule, reason: 'completed');

            return;
        }

        if ($schedule->wasChanged(['scheduled_at', 'completed_at', 'name', 'message'])) {
            $reason = $schedule->wasChanged(['scheduled_at', 'completed_at']) ? 'rescheduled' : 'updated';
            $this->notifier->scheduleUpdated($schedule, reason: $reason);
        }
    }
}
