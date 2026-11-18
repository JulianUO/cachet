<?php

namespace App\Support;

use Cachet\Enums\ComponentStatusEnum;
use Carbon\CarbonInterval;

/**
 * Helpers for the Atlassian-style uptime bars on the status page.
 */
class UptimeBar
{
    /**
     * CSS modifier for a day's bar (see the styles in the system-status view).
     */
    public static function modifier(?ComponentStatusEnum $status): string
    {
        return match ($status) {
            ComponentStatusEnum::operational => 'operational',
            ComponentStatusEnum::performance_issues => 'degraded',
            ComponentStatusEnum::partial_outage => 'partial',
            ComponentStatusEnum::major_outage => 'major',
            ComponentStatusEnum::under_maintenance => 'maintenance',
            default => 'nodata',
        };
    }

    /**
     * Human duration such as "2h 5m", rounded up to the minute.
     */
    public static function duration(float $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return CarbonInterval::minutes($minutes)
            ->cascade()
            ->forHumans(['parts' => 2, 'short' => true]);
    }

    /**
     * Non-operational statuses for a day, most severe first.
     *
     * @param  array<int, float>  $durations
     * @return list<array{status: ComponentStatusEnum, seconds: float}>
     */
    public static function downtime(array $durations): array
    {
        return collect($durations)
            ->map(fn (float $seconds, int $status): array => [
                'status' => ComponentStatusEnum::from($status),
                'seconds' => $seconds,
            ])
            ->filter(fn (array $row): bool => $row['seconds'] > 0)
            ->sortByDesc(fn (array $row): int => $row['status']->severity())
            ->values()
            ->all();
    }
}
