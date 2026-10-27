<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth;

enum Role: string
{
    case PublicViewer = 'public_viewer';
    case RegisteredViewer = 'registered_viewer';
    case ItSupport = 'it_support';
    case DevOps = 'devops';

    public function label(): string
    {
        return match ($this) {
            self::PublicViewer => __('Public viewer'),
            self::RegisteredViewer => __('Registered viewer'),
            self::ItSupport => __('IT support'),
            self::DevOps => __('DevOps'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
