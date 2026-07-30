<?php

namespace App\Enum;

final class BankingMovementType
{
    /**
     * Represents a standard transfer between two registered financial accounts.
     */
    public const BANK_TRANSFER = 'bank_transfer';

    /**
     * Represents a transfer from another account to a savings account.
     */
    public const SAVINGS_DEPOSIT = 'savings_deposit';

    /**
     * Represents a transfer from a savings account to another account.
     */
    public const SAVINGS_WITHDRAWAL = 'savings_withdrawal';

    /**
     * Represents a contribution made to an investment or life insurance account.
     */
    public const INVESTMENT_CONTRIBUTION = 'investment_contribution';

    /**
     * Represents a redemption or withdrawal from an investment or life insurance account.
     */
    public const INVESTMENT_REDEMPTION = 'investment_redemption';

    /**
     * Represents the purchase of securities or other financial assets.
     */
    public const SECURITIES_PURCHASE = 'securities_purchase';

    /**
     * Represents the sale of securities or other financial assets.
     */
    public const SECURITIES_SALE = 'securities_sale';

    /**
     * Represents the transfer of a remaining balance when an account is closed.
     */
    public const ACCOUNT_CLOSURE_TRANSFER = 'account_closure_transfer';

    /**
     * Represents another transfer that does not match the available movement types.
     */
    public const OTHER_TRANSFER = 'other_transfer';

    /**
     * Lists all banking movement types supported by the application.
     */
    public const MOVEMENT_TYPES = [
        self::BANK_TRANSFER,
        self::SAVINGS_DEPOSIT,
        self::SAVINGS_WITHDRAWAL,
        self::INVESTMENT_CONTRIBUTION,
        self::INVESTMENT_REDEMPTION,
        self::SECURITIES_PURCHASE,
        self::SECURITIES_SALE,
        self::ACCOUNT_CLOSURE_TRANSFER,
        self::OTHER_TRANSFER,
    ];

    /**
     * Checks whether the provided banking movement type is supported.
     */
    public static function isValid(string $movementType): bool
    {
        return in_array($movementType, self::MOVEMENT_TYPES, true);
    }
}