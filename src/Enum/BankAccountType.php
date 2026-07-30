<?php

namespace App\Enum;

final class BankAccountType
{
    // Current accounts
    public const CURRENT_ACCOUNT = 'current_account';
    public const JOINT_ACCOUNT = 'joint_account';

    // Savings accounts
    public const LIVRET_A = 'livret_a';
    public const LDDS = 'ldds';
    public const LEP = 'lep';
    public const PEL = 'pel';
    public const PEP = 'pep';

    // Investment accounts
    public const PEA = 'pea';
    public const SECURITIES_ACCOUNT = 'securities_account';
    public const LIFE_INSURANCE = 'life_insurance';
    public const CAPITALIZATION_CONTRACT = 'capitalization_contract';

    // Other accounts
    public const OTHER = 'other';

    public const ACCOUNT_TYPES = [
        self::CURRENT_ACCOUNT,
        self::JOINT_ACCOUNT,
        self::LIVRET_A,
        self::LDDS,
        self::LEP,
        self::PEL,
        self::PEP,
        self::PEA,
        self::SECURITIES_ACCOUNT,
        self::LIFE_INSURANCE,
        self::CAPITALIZATION_CONTRACT,
        self::OTHER,
    ];

    /**
     * Checks whether the given bank account type is valid.
     */
    public static function isValid(string $accountType): bool
    {
        return in_array($accountType, self::ACCOUNT_TYPES, true);
    }
}