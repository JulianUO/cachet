<?php

namespace App\Slack;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SlackDeliveryLog
{
    private const FAILURE_KEY = 'slack_notifications.last_failure';

    private const SUCCESS_KEY = 'slack_notifications.last_success_at';

    /**
     * @param  array<string, mixed>  $context
     */
    public static function success(string $event, array $context = []): void
    {
        Cache::put(self::SUCCESS_KEY, now()->toIso8601String(), now()->addDays(30));
        Cache::forget(self::FAILURE_KEY);

        Log::info('Slack notification delivered.', array_merge(['event' => $event], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function failure(string $event, string $message, array $context = []): void
    {
        $payload = [
            'event' => $event,
            'message' => $message,
            'context' => $context,
            'at' => now()->toIso8601String(),
        ];

        Cache::put(self::FAILURE_KEY, $payload, now()->addDays(30));

        Log::warning('Slack notification failed.', $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function lastFailure(): ?array
    {
        $value = Cache::get(self::FAILURE_KEY);

        return is_array($value) ? $value : null;
    }

    public static function lastSuccessAt(): ?string
    {
        $value = Cache::get(self::SUCCESS_KEY);

        return is_string($value) ? $value : null;
    }
}
