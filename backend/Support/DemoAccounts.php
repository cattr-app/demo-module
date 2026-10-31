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
     * Accounts whose work the emulator imitates: everyone except the administrator.
     *
     * @return Collection<int, User>
     */
    public static function workers(): Collection
    {
        return User::whereIn('email', self::emails())->where('role_id', '!=', Role::ADMIN)->get();
    }
}
