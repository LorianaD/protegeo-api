<?php

namespace App\Enum;

/**
 * Defines the detailed categories available for a financial transaction.
 */
final class TransactionCategoryType
{
    /*
     * Resources
     */

    // Income
    public const SALARY = 'salary';
    public const RETIREMENT_PENSION = 'retirement_pension';
    public const DISABILITY_PENSION = 'disability_pension';
    public const ALIMONY = 'alimony';
    public const LIFE_ANNUITY = 'life_annuity';
    public const RENTAL_INCOME = 'rental_income';

    // Allowances
    public const DISABLED_ADULT_ALLOWANCE = 'disabled_adult_allowance';
    public const FAMILY_ALLOWANCE = 'family_allowance';
    public const HOUSING_ALLOWANCE = 'housing_allowance';
    public const UNEMPLOYMENT_ALLOWANCE = 'unemployment_allowance';
    public const MINIMUM_INCOME = 'minimum_income';

    // Investment income
    public const INTERESTS_AND_DIVIDENDS = 'interests_and_dividends';

    // Other resources
    public const REAL_ESTATE_SALE = 'real_estate_sale';
    public const MOVABLE_PROPERTY_SALE = 'movable_property_sale';
    public const HEALTHCARE_REIMBURSEMENT = 'healthcare_reimbursement';
    public const OTHER_RESOURCE = 'other_resource';

    /*
     * Expenses
     */

    // Current expenses
    public const CLOTHING = 'clothing';
    public const FOOD = 'food';
    public const LEISURE_AND_HOLIDAYS = 'leisure_and_holidays';
    public const MEDICAL_EXPENSES = 'medical_expenses';
    public const SCHOOL_EXPENSES = 'school_expenses';
    public const POCKET_MONEY = 'pocket_money';
    public const OTHER_CURRENT_EXPENSE = 'other_current_expense';

    // Housing
    public const RENT = 'rent';
    public const ACCOMMODATION_EXPENSES = 'accommodation_expenses';
    public const ELECTRICITY = 'electricity';
    public const GAS = 'gas';
    public const WATER = 'water';
    public const TELEPHONE = 'telephone';

    // Insurance
    public const HOME_INSURANCE = 'home_insurance';
    public const CAR_INSURANCE = 'car_insurance';
    public const HEALTH_INSURANCE = 'health_insurance';
    public const OTHER_INSURANCE = 'other_insurance';

    // Home care
    public const HOUSEKEEPING_HELP = 'housekeeping_help';
    public const HOUSEHOLD_EMPLOYEE = 'household_employee';
    public const OTHER_HOME_CARE = 'other_home_care';

    // Taxes
    public const INCOME_TAX = 'income_tax';
    public const HOUSING_TAX = 'housing_tax';
    public const PROPERTY_TAX = 'property_tax';
    public const TV_LICENSE = 'tv_license';

    // Major purchases
    public const REAL_ESTATE_PURCHASE = 'real_estate_purchase';
    public const VEHICLE_PURCHASE = 'vehicle_purchase';
    public const FURNITURE_PURCHASE = 'furniture_purchase';
    public const OTHER_MAJOR_PURCHASE = 'other_major_purchase';

    // Investments
    public const INVESTMENT = 'investment';

    // Repairs
    public const PROPERTY_RENOVATION = 'property_renovation';
    public const MAINTENANCE_REPAIR = 'maintenance_repair';
    public const OTHER_REPAIR = 'other_repair';

    // Loans
    public const LOAN_REPAYMENT = 'loan_repayment';

    // Other expenses
    public const PRIVATE_MANAGER_FEES = 'private_manager_fees';
    public const OTHER_EXPENSE = 'other_expense';

    public const TYPES = [
        self::SALARY,
        self::RETIREMENT_PENSION,
        self::DISABILITY_PENSION,
        self::ALIMONY,
        self::LIFE_ANNUITY,
        self::RENTAL_INCOME,

        self::DISABLED_ADULT_ALLOWANCE,
        self::FAMILY_ALLOWANCE,
        self::HOUSING_ALLOWANCE,
        self::UNEMPLOYMENT_ALLOWANCE,
        self::MINIMUM_INCOME,

        self::INTERESTS_AND_DIVIDENDS,

        self::REAL_ESTATE_SALE,
        self::MOVABLE_PROPERTY_SALE,
        self::HEALTHCARE_REIMBURSEMENT,
        self::OTHER_RESOURCE,

        self::CLOTHING,
        self::FOOD,
        self::LEISURE_AND_HOLIDAYS,
        self::MEDICAL_EXPENSES,
        self::SCHOOL_EXPENSES,
        self::POCKET_MONEY,
        self::OTHER_CURRENT_EXPENSE,

        self::RENT,
        self::ACCOMMODATION_EXPENSES,
        self::ELECTRICITY,
        self::GAS,
        self::WATER,
        self::TELEPHONE,

        self::HOME_INSURANCE,
        self::CAR_INSURANCE,
        self::HEALTH_INSURANCE,
        self::OTHER_INSURANCE,

        self::HOUSEKEEPING_HELP,
        self::HOUSEHOLD_EMPLOYEE,
        self::OTHER_HOME_CARE,

        self::INCOME_TAX,
        self::HOUSING_TAX,
        self::PROPERTY_TAX,
        self::TV_LICENSE,

        self::REAL_ESTATE_PURCHASE,
        self::VEHICLE_PURCHASE,
        self::FURNITURE_PURCHASE,
        self::OTHER_MAJOR_PURCHASE,

        self::INVESTMENT,

        self::PROPERTY_RENOVATION,
        self::MAINTENANCE_REPAIR,
        self::OTHER_REPAIR,

        self::LOAN_REPAYMENT,

        self::PRIVATE_MANAGER_FEES,
        self::OTHER_EXPENSE,
    ];

    public static function isValid(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }
}