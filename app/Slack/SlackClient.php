<?php

namespace App\Slack;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SlackClient
{
    /**
     * @param  array{text: string, blocks?: list<array<string, mixed>>, attachments?: list<array<string, mixed>>}  $message
     * @return list<array{method: string, ok: bool, body: string}>
     */
    public function send(array $message): array
    {
        $results = [];

        foreach (SlackNotifications::deliveryMethods() as $method) {
            $results[] = match ($method) {
                'incoming_webhook' => $this->sendWebhook($message),
                'bot_token' => $this->sendBot($message),
                default => throw new RuntimeException("Unknown Slack delivery method [{$method}]."),
            };
        }

        if ($results === []) {
            throw new RuntimeException('No Slack delivery method is configured.');
        }

        return $results;
    }

    /**
     * @param  array{text: string, blocks?: list<array<string, mixed>>, attachments?: list<array<string, mixed>>}  $message
     * @return array{method: string, ok: bool, body: string}
     */
    private function sendWebhook(array $message): array
    {
        $url = (string) config('slack_notifications.webhook_url');

        $payload = array_filter([
            'text' => $message['text'],
            'username' => config('slack_notifications.username'),
            'icon_emoji' => config('slack_notifications.icon_emoji'),
            'blocks' => $message['blocks'] ?? null,
            'attachments' => $message['attachments'] ?? null,
        ], fn ($value) => $value !== null);

        try {
            $response = Http::timeout((int) config('slack_notifications.timeout', 10))
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Slack webhook connection failed: '.$exception->getMessage(), 0, $exception);
        }

        $body = trim($response->body());
        // Incoming webhooks respond with plain-text "ok" on success.
        $ok = $response->successful() && ($body === 'ok' || $response->json('ok') === true);

        if (! $ok) {
            throw new RuntimeException('Slack webhook rejected the payload: '.$body);
        }

        return ['method' => 'incoming_webhook', 'ok' => true, 'body' => $body];
    }

    /**
     * @param  array{text: string, blocks?: list<array<string, mixed>>, attachments?: list<array<string, mixed>>}  $message
     * @return array{method: string, ok: bool, body: string}
     */
    private function sendBot(array $message): array
    {
        $token = (string) config('slack_notifications.bot_token');
        $channel = (string) config('slack_notifications.channel');

        $payload = array_filter([
            'channel' => $channel,
            'text' => $message['text'],
            'username' => config('slack_notifications.username'),
            'icon_emoji' => config('slack_notifications.icon_emoji'),
            'blocks' => $message['blocks'] ?? null,
            'attachments' => $message['attachments'] ?? null,
        ], fn ($value) => $value !== null);

        try {
            $response = Http::timeout((int) config('slack_notifications.timeout', 10))
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->post('https://slack.com/api/chat.postMessage', $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Slack bot API connection failed: '.$exception->getMessage(), 0, $exception);
        }

        $body = $response->body();
        $ok = $response->successful() && $response->json('ok') === true;

        if (! $ok) {
            $error = $response->json('error') ?? $body;
            throw new RuntimeException('Slack bot API error: '.(is_string($error) ? $error : json_encode($error)));
        }

        return ['method' => 'bot_token', 'ok' => true, 'body' => $body];
    }
}
