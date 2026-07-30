<?php

namespace App\Enum;

/**
 * Defines the transaction types available for a financial transaction.
 */
final class TransactionType
{
    public const RESOURCE = 'resource';
    public const EXPENSE = 'expense';

    public const TYPES = [
        self::RESOURCE,
        self::EXPENSE,
    ];

    public static function isValid(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }
}