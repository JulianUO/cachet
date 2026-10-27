<?php

namespace App\Slack;

use Cachet\Cachet;
use Cachet\Enums\ComponentStatusEnum;
use Cachet\Models\Component;
use Cachet\Models\Incident;
use Cachet\Models\Schedule;
use Cachet\Models\Update;
use Illuminate\Support\Str;

class SlackMessageBuilder
{
    /**
     * @return array{text: string, blocks: list<array<string, mixed>>}
     */
    public function incidentCreated(Incident $incident): array
    {
        $status = $incident->status?->getLabel() ?? 'Unknown';
        $url = $this->incidentUrl($incident);
        $components = $this->componentNames($incident);

        return $this->message(
            text: "Incident: {$incident->name} ({$status})",
            title: 'New incident',
            fields: [
                'Incident' => $incident->name,
                'Status' => $status,
                'Components' => $components,
            ],
            url: $url,
            color: $this->incidentColor($incident),
        );
    }

    /**
     * @return array{text: string, blocks: list<array<string, mixed>>}
     */
    public function incidentUpdated(Incident $incident, ?Update $update = null): array
    {
        $status = $update?->status?->getLabel() ?? $incident->status?->getLabel() ?? 'Updated';
        $url = $this->incidentUrl($incident);
        $detail = $update?->message
            ? Str::limit(strip_tags($update->formattedMessage()), 280)
            : Str::limit(strip_tags((string) $incident->message), 280);

        return $this->message(
            text: "Incident update: {$incident->name} ({$status})",
            title: 'Incident updated',
            fields: [
                'Incident' => $incident->name,
                'Status' => $status,
                'Details' => $detail !== '' ? $detail : '—',
                'Components' => $this->componentNames($incident),
            ],
            url: $url,
            color: $this->incidentColor($incident),
        );
    }

    /**
     * @return array{text: string, blocks: list<array<string, mixed>>}
     */
    public function componentStatusChanged(
        Component $component,
        ?ComponentStatusEnum $oldStatus,
        ComponentStatusEnum $newStatus,
    ): array {
        $url = url(Cachet::path());

        return $this->message(
            text: "Component status: {$component->name} is now {$newStatus->getLabel()}",
            title: 'Component status changed',
            fields: [
                'Component' => $component->name,
                'Previous' => $oldStatus?->getLabel() ?? '—',
                'Current' => $newStatus->getLabel(),
            ],
            url: $url,
            color: $this->componentColor($newStatus),
        );
    }

    /**
     * @return array{text: string, blocks: list<array<string, mixed>>}
     */
    public function scheduleCreated(Schedule $schedule): array
    {
        return $this->message(
            text: "Maintenance scheduled: {$schedule->name}",
            title: 'Maintenance scheduled',
            fields: [
                'Maintenance' => $schedule->name,
                'Starts' => $schedule->scheduled_at?->toDayDateTimeString() ?? '—',
                'Ends' => $schedule->completed_at?->toDayDateTimeString() ?? '—',
                'Components' => $this->componentNames($schedule),
            ],
            url: $this->scheduleUrl($schedule),
            color: '#f97316',
        );
    }

    /**
     * @return array{text: string, blocks: list<array<string, mixed>>}
     */
    public function scheduleUpdated(Schedule $schedule, ?Update $update = null, ?string $reason = null): array
    {
        $title = match ($reason) {
            'completed' => 'Maintenance completed',
            'rescheduled' => 'Maintenance rescheduled',
            default => 'Maintenance updated',
        };

        $detail = $update?->message
            ? Str::limit(strip_tags($update->formattedMessage()), 280)
            : ($reason === 'completed' ? 'Maintenance window finished.' : '—');

        return $this->message(
            text: "{$title}: {$schedule->name}",
            title: $title,
            fields: [
                'Maintenance' => $schedule->name,
                'Starts' => $schedule->scheduled_at?->toDayDateTimeString() ?? '—',
                'Ends' => $schedule->completed_at?->toDayDateTimeString() ?? '—',
                'Details' => $detail,
                'Components' => $this->componentNames($schedule),
            ],
            url: $this->scheduleUrl($schedule),
            color: $reason === 'completed' ? '#22c55e' : '#f97316',
        );
    }

    /**
     * @param  array<string, string>  $fields
     * @return array{text: string, blocks: list<array<string, mixed>>}
     */
    private function message(string $text, string $title, array $fields, string $url, string $color): array
    {
        $fieldBlocks = [];
        foreach ($fields as $label => $value) {
            $fieldBlocks[] = [
                'type' => 'mrkdwn',
                'text' => '*'.$label.":*\n".$value,
            ];
        }

        return [
            'text' => $text,
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => $title,
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => array_slice($fieldBlocks, 0, 10),
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'View on status page',
                            ],
                            'url' => $url,
                        ],
                    ],
                ],
                [
                    'type' => 'context',
                    'elements' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => 'Sent by Cachet native Slack notifications · color hint `'.$color.'`',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function incidentUrl(Incident $incident): string
    {
        return route('cachet.status-page.incident', $incident);
    }

    private function scheduleUrl(Schedule $schedule): string
    {
        return route('cachet.status-page.schedule', $schedule);
    }

    private function componentNames(Incident|Schedule $model): string
    {
        $names = $model->relationLoaded('components')
            ? $model->components->pluck('name')
            : $model->components()->pluck('name');

        $list = $names->filter()->values();

        return $list->isEmpty() ? '—' : $list->implode(', ');
    }

    private function incidentColor(Incident $incident): string
    {
        return match ($incident->status?->value) {
            4 => '#ef4444', // fixed often green, but major statuses vary; keep generic
            default => '#f59e0b',
        };
    }

    private function componentColor(ComponentStatusEnum $status): string
    {
        return match ($status) {
            ComponentStatusEnum::operational => '#22c55e',
            ComponentStatusEnum::major_outage => '#ef4444',
            ComponentStatusEnum::under_maintenance => '#f97316',
            default => '#f59e0b',
        };
    }
}
