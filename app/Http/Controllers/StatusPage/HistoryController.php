<?php

namespace App\Http\Controllers\StatusPage;

use Cachet\Models\Incident;
use Cachet\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HistoryController
{
    /**
     * Show a month calendar of incident and maintenance history.
     */
    public function index(Request $request): View
    {
        $month = rescue(
            fn () => Carbon::createFromFormat('Y-m', (string) $request->query('month', now()->format('Y-m')))->startOfMonth(),
            fn () => now()->startOfMonth(),
            report: false
        );

        $selectedDay = rescue(
            function () use ($request, $month) {
                $day = $request->query('day');

                if (! is_string($day) || $day === '') {
                    return null;
                }

                $parsed = Carbon::createFromFormat('Y-m-d', $day)->startOfDay();

                return $parsed->isSameMonth($month) ? $parsed : null;
            },
            fn () => null,
            report: false
        );

        $start = $month->clone()->startOfMonth()->startOfDay();
        $end = $month->clone()->endOfMonth()->endOfDay();

        $incidents = $this->incidents($start, $end);
        $schedules = $this->schedules($start, $end);

        $days = collect($start->toPeriod($end->copy()->startOfDay()))
            ->mapWithKeys(function (Carbon $day) use ($incidents, $schedules): array {
                $date = $day->toDateString();

                return [$date => [
                    'date' => $day->copy(),
                    'incidents' => $incidents->get($date, collect()),
                    'schedules' => $schedules->get($date, collect()),
                ]];
            });

        $calendarStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);
        $cells = collect($calendarStart->toPeriod($calendarEnd))->map(function (Carbon $day) use ($month, $days): array {
            $date = $day->toDateString();
            $entry = $days->get($date);

            return [
                'date' => $day->copy(),
                'in_month' => $day->isSameMonth($month),
                'incidents' => $entry['incidents'] ?? collect(),
                'schedules' => $entry['schedules'] ?? collect(),
                'has_events' => $entry !== null && ($entry['incidents']->isNotEmpty() || $entry['schedules']->isNotEmpty()),
            ];
        });

        $selected = $selectedDay
            ? $days->get($selectedDay->toDateString(), [
                'date' => $selectedDay,
                'incidents' => collect(),
                'schedules' => collect(),
            ])
            : null;

        return view('status-page.history', [
            'month' => $month,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'canGoForward' => $month->copy()->addMonth()->startOfMonth()->lte(now()->startOfMonth()),
            'cells' => $cells,
            'selectedDay' => $selectedDay,
            'selected' => $selected,
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ]);
    }

    /**
     * @return Collection<string, Collection<int, Incident>>
     */
    private function incidents(Carbon $start, Carbon $end): Collection
    {
        return Incident::query()
            ->with(['components', 'updates'])
            ->viewableBy(auth()->check())
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('occurred_at', [
                    $start->toDateTimeString(),
                    $end->toDateTimeString(),
                ])->orWhere(function ($query) use ($start, $end): void {
                    $query->whereNull('occurred_at')->whereBetween('created_at', [
                        $start->toDateTimeString(),
                        $end->toDateTimeString(),
                    ]);
                });
            })
            ->get()
            ->groupBy(fn (Incident $incident): string => $incident->timestamp->toDateString());
    }

    /**
     * @return Collection<string, Collection<int, Schedule>>
     */
    private function schedules(Carbon $start, Carbon $end): Collection
    {
        /** @var Collection<string, Collection<int, Schedule>> $grouped */
        $grouped = collect();

        Schedule::query()
            ->with(['components'])
            ->published()
            ->where('scheduled_at', '<=', $end)
            ->where(fn ($query) => $query->whereNull('completed_at')->orWhere('completed_at', '>=', $start))
            ->get()
            ->each(function (Schedule $schedule) use ($start, $end, $grouped): void {
                $from = ($schedule->scheduled_at ?? $start)->copy()->maximum($start)->startOfDay();
                $to = ($schedule->completed_at ?? $end)->copy()->minimum($end)->startOfDay();

                if ($from->gt($to)) {
                    return;
                }

                foreach ($from->toPeriod($to) as $day) {
                    $date = $day->toDateString();

                    if (! $grouped->has($date)) {
                        $grouped->put($date, collect());
                    }

                    $grouped->get($date)->push($schedule);
                }
            });

        return $grouped;
    }
}
