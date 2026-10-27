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

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class ExternalUserProvisioner
{
    public function __construct(
        private readonly PermissionMatrix $matrix,
    ) {}

    /**
     * Find or create a local user for an external identity provider.
     *
     * @param  array{external_id: string, email: string, name: ?string, groups?: list<string>}  $identity
     */
    public function provision(string $provider, array $identity, bool $autoProvision, bool $defaultIsAdmin): User
    {
        $externalId = trim((string) ($identity['external_id'] ?? ''));
        $email = Str::lower(trim((string) ($identity['email'] ?? '')));
        $name = trim((string) ($identity['name'] ?? '')) ?: ($email !== '' ? Str::before($email, '@') : 'User');
        $groups = array_values(array_filter(array_map('strval', $identity['groups'] ?? [])));

        if ($externalId === '') {
            throw new RuntimeException("The {$provider} identity is missing an external id.");
        }

        if ($email === '') {
            throw new RuntimeException("The {$provider} identity is missing an email address.");
        }

        $user = User::query()
            ->where('auth_provider', $provider)
            ->where('external_id', $externalId)
            ->first();

        if ($user === null) {
            $user = User::query()->where('email', $email)->first();
        }

        $isNew = $user === null;

        if ($user === null) {
            if (! $autoProvision) {
                throw new RuntimeException("No Cachet user exists for this {$provider} identity and auto-provisioning is disabled.");
            }

            $user = new User;
            $user->is_admin = $defaultIsAdmin;
            $user->email_verified_at = now();
        }

        $mappedRole = $this->matrix->mapExternalGroups($groups, $provider);

        $user->fill([
            'name' => $name,
            'email' => $email,
            'auth_provider' => $provider,
            'external_id' => $externalId,
            'password' => null,
        ]);

        if ($mappedRole !== null) {
            $user->role = $mappedRole;
        } elseif ($isNew || blank($user->role)) {
            $user->role = (string) config('authorization.default_role', Role::RegisteredViewer->value);
        }

        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }

        $user->save();

        return $user;
    }
}
