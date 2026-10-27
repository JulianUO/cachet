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
        $status = $incident->status?->getLabel() ?? __('Unknown');
        $url = $this->incidentUrl($incident);
        $components = $this->componentNames($incident);

        return $this->message(
            text: __('Incident: :name (:status)', ['name' => $incident->name, 'status' => $status]),
            title: __('New incident'),
            fields: [
                __('Incident') => $incident->name,
                __('Status') => $status,
                __('Components') => $components,
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
        $status = $update?->status?->getLabel() ?? $incident->status?->getLabel() ?? __('Updated');
        $url = $this->incidentUrl($incident);
        $detail = $update?->message
            ? Str::limit(strip_tags($update->formattedMessage()), 280)
            : Str::limit(strip_tags((string) $incident->message), 280);

        return $this->message(
            text: __('Incident update: :name (:status)', ['name' => $incident->name, 'status' => $status]),
            title: __('Incident updated'),
            fields: [
                __('Incident') => $incident->name,
                __('Status') => $status,
                __('Details') => $detail !== '' ? $detail : '—',
                __('Components') => $this->componentNames($incident),
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
            text: __('Component status: :name is now :status', [
                'name' => $component->name,
                'status' => $newStatus->getLabel(),
            ]),
            title: __('Component status changed'),
            fields: [
                __('Component') => $component->name,
                __('Previous') => $oldStatus?->getLabel() ?? '—',
                __('Current') => $newStatus->getLabel(),
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
            text: __('Maintenance scheduled: :name', ['name' => $schedule->name]),
            title: __('Maintenance scheduled'),
            fields: [
                __('Maintenance') => $schedule->name,
                __('Starts') => $schedule->scheduled_at?->toDayDateTimeString() ?? '—',
                __('Ends') => $schedule->completed_at?->toDayDateTimeString() ?? '—',
                __('Components') => $this->componentNames($schedule),
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
            'completed' => __('Maintenance completed'),
            'rescheduled' => __('Maintenance rescheduled'),
            default => __('Maintenance updated'),
        };

        $detail = $update?->message
            ? Str::limit(strip_tags($update->formattedMessage()), 280)
            : ($reason === 'completed' ? __('Maintenance window finished.') : '—');

        return $this->message(
            text: "{$title}: {$schedule->name}",
            title: $title,
            fields: [
                __('Maintenance') => $schedule->name,
                __('Starts') => $schedule->scheduled_at?->toDayDateTimeString() ?? '—',
                __('Ends') => $schedule->completed_at?->toDayDateTimeString() ?? '—',
                __('Details') => $detail,
                __('Components') => $this->componentNames($schedule),
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
                                'text' => __('View on status page'),
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
                            'text' => __('Sent by Cachet native Slack notifications · color hint `:color`', ['color' => $color]),
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
