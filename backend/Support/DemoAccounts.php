<?php

namespace Modules\Demo\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Demo accounts owned by the module: created by DemoSeeder, signed in without a password through
 * the demo login endpoint and protected from edits that would break that sign-in.
 */
class DemoAccounts
{
    private const EMAIL_DOMAIN = 'demo.cattr.app';

    /**
     * key => global role and, for project-level roles, the role in the account's projects.
     * The order is the order of the login page selector.
     */
    public const ACCOUNTS = [
        'admin' => ['role' => Role::ADMIN, 'project_role' => null],
        'manager' => ['role' => Role::MANAGER, 'project_role' => null],
        'auditor' => ['role' => Role::AUDITOR, 'project_role' => null],
        'project_manager' => ['role' => Role::USER, 'project_role' => Role::MANAGER],
        'project_auditor' => ['role' => Role::USER, 'project_role' => Role::AUDITOR],
        'user' => ['role' => Role::USER, 'project_role' => null],
    ];

    /** @return string[] */
    public static function keys(): array
    {
        return array_keys(self::ACCOUNTS);
    }

    public static function email(string $key): string
    {
        return str_replace('_', '-', $key) . '@' . self::EMAIL_DOMAIN;
    }

    /** @return string[] */
    public static function emails(): array
    {
        return array_map(self::email(...), self::keys());
    }

    public static function find(string $key): ?User
    {
        return isset(self::ACCOUNTS[$key]) ? User::firstWhere('email', self::email($key)) : null;
    }

    public static function isDemoAccount(int|string|null $userId): bool
    {
        return $userId !== null && User::whereKey($userId)->whereIn('email', self::emails())->exists();
    }

    /**
     * Profile of a demo user the emulator imitates: the login accounts except the administrator
     * work in the application timezone, team members in their own.
     *
     * @return array{timezone: ?string, shift: int, profile: string}|null
     */
    public static function workProfile(string $email): ?array
    {
        foreach (DemoCatalog::ACCOUNT_PERSONAS as $key => $persona) {
            if ($key !== 'admin' && self::email($key) === $email) {
                return ['timezone' => null, 'shift' => 9, 'profile' => $persona['profile']];
            }
        }

        foreach (DemoCatalog::TEAM as $key => $member) {
            if (DemoCatalog::teamEmail($key) === $email) {
                return ['timezone' => $member['timezone'], 'shift' => $member['shift'], 'profile' => $member['profile']];
            }
        }

        return null;
    }

    /**
     * Demo users whose work the emulator imitates, see workProfile().
     *
     * @return Collection<int, User>
     */
    public static function workers(): Collection
    {
        $emails = array_merge(
            array_map(self::email(...), array_diff(self::keys(), ['admin'])),
            array_map(DemoCatalog::teamEmail(...), array_keys(DemoCatalog::TEAM)),
        );

        return User::whereIn('email', $emails)->orderBy('id')->get();
    }
}
