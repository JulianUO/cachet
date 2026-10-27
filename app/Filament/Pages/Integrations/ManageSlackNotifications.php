<?php

namespace App\Filament\Pages\Integrations;

use App\Auth\Permission;
use App\Models\User;
use App\Slack\SlackNotifications;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Admin view of native Slack notification status.
 * Enable/disable and credentials are configured via environment variables
 * (see config/slack_notifications.php and .env.example).
 */
class ManageSlackNotifications extends Page
{
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

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return SlackNotifications::status();
    }
}
