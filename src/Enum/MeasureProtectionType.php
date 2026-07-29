<?php

namespace App\Enum;

/**
 * Defines the available types of legal protection measures.
 */
final class MeasureProtectionType
{
    public const SAFEGUARD_OF_JUSTICE = 'safeguard_of_justice';
    public const SIMPLE_CURATORSHIP = 'simple_curatorship';
    public const REINFORCED_CURATORSHIP = 'reinforced_curatorship';
    public const ADAPTED_CURATORSHIP = 'adapted_curatorship';
    public const GUARDIANSHIP = 'guardianship';
    public const FAMILY_AUTHORIZATION = 'family_authorization';
    public const FUTURE_PROTECTION_MANDATE = 'future_protection_mandate';

    /**
     * List of all available legal protection measure types.
     */
    public const TYPES = [
        self::SAFEGUARD_OF_JUSTICE,
        self::SIMPLE_CURATORSHIP,
        self::REINFORCED_CURATORSHIP,
        self::ADAPTED_CURATORSHIP,
        self::GUARDIANSHIP,
        self::FAMILY_AUTHORIZATION,
        self::FUTURE_PROTECTION_MANDATE,
    ];

    /**
     * Checks whether the given measure type is valid.
     */
    public static function isValid(string $measureType): bool
    {
        return in_array($measureType, self::TYPES, true);
    }
}