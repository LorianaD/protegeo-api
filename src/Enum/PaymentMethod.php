<?php

namespace App\Enum;

/**
 * Defines the payment methods available for a financial transaction.
 */
final class PaymentMethod
{
    public const BANK_CARD = 'bank_card';
    public const CHECK = 'check';
    public const BANK_TRANSFER = 'bank_transfer';
    public const DIRECT_DEBIT = 'direct_debit';
    public const CASH = 'cash';
    public const OTHER = 'other';

    public const METHODS = [
        self::BANK_CARD,
        self::CHECK,
        self::BANK_TRANSFER,
        self::DIRECT_DEBIT,
        self::CASH,
        self::OTHER,
    ];

    public static function isValid(string $method): bool
    {
        return in_array($method, self::METHODS, true);
    }
}