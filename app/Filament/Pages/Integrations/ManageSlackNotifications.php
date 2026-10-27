<?php

namespace App\Filament\Pages\Integrations;

use App\Auth\Permission;
use App\Models\User;
use App\Settings\SlackNotificationSettings;
use App\Slack\SlackDeliveryLog;
use App\Slack\SlackNotifications;
use Cachet\Filament\Pages\Settings\SettingsPage;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ManageSlackNotifications extends SettingsPage
{
    protected static string $settings = SlackNotificationSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected string $view = 'filament.pages.integrations.manage-slack-notifications';

    public static function getNavigationGroup(): ?string
    {
        return __('cachet::navigation.integrations.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('Slack notifications');
    }

    public function getTitle(): string
    {
        return __('Slack notifications');
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->isAdmin()
            || $user->hasPermission(Permission::SettingsManage)
            || $user->hasPermission(Permission::WebhooksManage)
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Native Slack notifications'))
                    ->description(__('Post incidents, component status changes, and maintenance into Slack. Preferred over the legacy /slack.rss feed. Configure everything here — no environment variables required.'))
                    ->schema([
                        Toggle::make('enabled')
                            ->label(__('Enable Slack notifications'))
                            ->helperText(__('When enabled and a delivery method is configured, Cachet posts status events to Slack.'))
                            ->live(),
                    ]),

                Section::make(__('Incoming Webhook'))
                    ->description(__('Preferred delivery method. Create an Incoming Webhook in Slack and paste the URL.'))
                    ->schema([
                        TextInput::make('webhook_url')
                            ->label(__('Webhook URL'))
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->maxLength(2048)
                            ->placeholder(fn (): string => SlackNotifications::webhookConfigured()
                                ? __('Leave blank to keep the current webhook URL')
                                : 'https://hooks.slack.com/services/…')
                            ->helperText(fn (): string => SlackNotifications::webhookConfigured()
                                ? __('A webhook URL is already saved. Enter a new value to replace it.')
                                : __('Stored securely in the database. Never logged in plain text.')),
                    ]),

                Section::make(__('Bot token + channel'))
                    ->description(__('Alternative when no webhook is set (or additionally if “also use bot” is enabled). Requires a Slack app with chat:write.'))
                    ->schema([
                        TextInput::make('bot_token')
                            ->label(__('Bot user OAuth token'))
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->maxLength(255)
                            ->placeholder(fn (): string => SlackNotifications::botConfigured()
                                ? __('Leave blank to keep the current bot token')
                                : 'xoxb-…')
                            ->helperText(fn (): string => SlackNotifications::botConfigured()
                                ? __('A bot token is already saved. Enter a new value to replace it.')
                                : __('Stored securely in the database. Never logged in plain text.')),
                        TextInput::make('channel')
                            ->label(__('Channel'))
                            ->maxLength(255)
                            ->placeholder('C0123456789 or #status')
                            ->helperText(__('Channel ID (recommended) or #channel-name.')),
                        Toggle::make('use_bot_when_webhook_set')
                            ->label(__('Also send via bot when a webhook is configured'))
                            ->helperText(__('By default the webhook is used alone when both are set.')),
                    ]),

                Section::make(__('Message appearance'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('username')
                            ->label(__('Display name'))
                            ->maxLength(80)
                            ->required(),
                        TextInput::make('icon_emoji')
                            ->label(__('Icon emoji'))
                            ->maxLength(40)
                            ->placeholder(':traffic_light:'),
                    ]),

                Section::make(__('Events'))
                    ->description(__('Choose which Cachet events post to Slack. Incidents and schedules still respect their per-item “notify subscribers” flags.'))
                    ->schema([
                        Toggle::make('notify_incidents')
                            ->label(__('Incidents (create / update)')),
                        Toggle::make('notify_component_status')
                            ->label(__('Component status changes')),
                        Toggle::make('notify_schedules')
                            ->label(__('Maintenance / schedules')),
                    ]),
            ]);
    }

    /**
     * Do not put secrets into the form state when loading.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['webhook_url'] = '';
        $data['bot_token'] = '';

        return $data;
    }

    /**
     * Keep existing secrets when the admin leaves those fields blank.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $settings = app(SlackNotificationSettings::class);

        if (! filled($data['webhook_url'] ?? null)) {
            $data['webhook_url'] = $settings->webhook_url;
        }

        if (! filled($data['bot_token'] ?? null)) {
            $data['bot_token'] = $settings->bot_token;
        }

        $data['webhook_url'] = filled($data['webhook_url'] ?? null) ? trim((string) $data['webhook_url']) : null;
        $data['bot_token'] = filled($data['bot_token'] ?? null) ? trim((string) $data['bot_token']) : null;
        $data['channel'] = filled($data['channel'] ?? null) ? trim((string) $data['channel']) : null;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return SlackNotifications::status();
    }

    public function getLastFailure(): ?array
    {
        return SlackDeliveryLog::lastFailure();
    }
}
