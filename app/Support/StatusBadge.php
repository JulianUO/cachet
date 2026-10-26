<?php

namespace App\Support;

use Cachet\Enums\ComponentStatusEnum;

class StatusBadge
{
    /**
     * Tailwind classes for Atlassian-style status pills on the status page.
     */
    public static function classes(?ComponentStatusEnum $status): string
    {
        $base = 'inline-flex shrink-0 items-center rounded px-2 py-0.5 text-xs font-medium ring-1 ring-inset';

        return $base.' '.match ($status) {
            ComponentStatusEnum::operational => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/30',
            ComponentStatusEnum::performance_issues => 'bg-purple-50 text-purple-800 ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-300 dark:ring-purple-400/30',
            ComponentStatusEnum::partial_outage => 'bg-amber-50 text-amber-900 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/30',
            ComponentStatusEnum::major_outage => 'bg-red-50 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/30',
            ComponentStatusEnum::under_maintenance => 'bg-orange-50 text-orange-900 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-400/30',
            default => 'bg-zinc-100 text-zinc-700 ring-zinc-500/20 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-white/15',
        };
    }
}
