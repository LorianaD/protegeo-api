<?php

namespace App\Enum;

/**
 * Defines the available contact categories.
 */
final class ContactCategory
{
    public const FAMILY = 'family';
    public const PROFESSIONAL = 'professional';
    public const ORGANIZATION = 'organization';

    /**
     * List of all available contact categories.
     */
    public const CATEGORIES = [
        self::FAMILY,
        self::PROFESSIONAL,
        self::ORGANIZATION,
    ];

    /**
     * Checks whether the given category is valid.
     */
    public static function isValid(string $category): bool
    {
        return in_array($category, self::CATEGORIES, true);
    }
}