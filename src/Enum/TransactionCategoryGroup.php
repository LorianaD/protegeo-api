<?php

namespace App\Enum;

/**
 * Defines the main category groups available for a financial transaction.
 */
final class TransactionCategoryGroup
{
    // Resource groups
    public const INCOME = 'income';
    public const ALLOWANCES = 'allowances';
    public const INVESTMENT_INCOME = 'investment_income';
    public const OTHER_RESOURCES = 'other_resources';

    // Expense groups
    public const CURRENT_EXPENSES = 'current_expenses';
    public const HOUSING = 'housing';
    public const INSURANCE = 'insurance';
    public const HOME_CARE = 'home_care';
    public const TAXES = 'taxes';
    public const MAJOR_PURCHASES = 'major_purchases';
    public const INVESTMENTS = 'investments';
    public const REPAIRS = 'repairs';
    public const LOANS = 'loans';
    public const OTHER_EXPENSES = 'other_expenses';

    public const GROUPS = [
        self::INCOME,
        self::ALLOWANCES,
        self::INVESTMENT_INCOME,
        self::OTHER_RESOURCES,

        self::CURRENT_EXPENSES,
        self::HOUSING,
        self::INSURANCE,
        self::HOME_CARE,
        self::TAXES,
        self::MAJOR_PURCHASES,
        self::INVESTMENTS,
        self::REPAIRS,
        self::LOANS,
        self::OTHER_EXPENSES,
    ];

    public static function isValid(string $group): bool
    {
        return in_array($group, self::GROUPS, true);
    }
}