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
        $safe = self::redact($context);

        Cache::put(self::SUCCESS_KEY, now()->toIso8601String(), now()->addDays(30));
        Cache::forget(self::FAILURE_KEY);

        Log::info('Slack notification delivered.', array_merge(['event' => $event], $safe));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function failure(string $event, string $message, array $context = []): void
    {
        $payload = [
            'event' => $event,
            'message' => self::redactString($message),
            'context' => self::redact($context),
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

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            if (is_string($value) && self::looksLikeSecret((string) $key, $value)) {
                $redacted[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = self::redact($value);

                continue;
            }

            $redacted[$key] = is_string($value) ? self::redactString($value) : $value;
        }

        return $redacted;
    }

    private static function redactString(string $value): string
    {
        $value = preg_replace('#https://hooks\.slack\.com/services/[^\s"\']+#i', 'https://hooks.slack.com/services/[redacted]', $value) ?? $value;
        $value = preg_replace('#xoxb-[A-Za-z0-9-]+#', 'xoxb-[redacted]', $value) ?? $value;

        return $value;
    }

    private static function looksLikeSecret(string $key, string $value): bool
    {
        $key = strtolower($key);

        if (str_contains($key, 'token') || str_contains($key, 'webhook') || str_contains($key, 'secret') || str_contains($key, 'password')) {
            return true;
        }

        return str_starts_with($value, 'xoxb-') || str_contains($value, 'hooks.slack.com/services/');
    }
}
