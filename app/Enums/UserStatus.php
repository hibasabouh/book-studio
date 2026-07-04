<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * Translation key explaining why a non-active account is blocked, or null
     * when the account is allowed to sign in and act.
     */
    public function blockMessageKey(): ?string
    {
        return match ($this) {
            self::Active => null,
            self::Inactive => 'auth.account_inactive',
            self::Suspended => 'auth.account_suspended',
        };
    }
}
