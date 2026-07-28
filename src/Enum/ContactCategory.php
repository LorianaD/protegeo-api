<?php

namespace App\Enum;

final class ContactCategory
{
    public const FAMILY = 'family';
    public const PROFESSIONAL = 'professional';
    public const ORGANIZATION = 'organization';

    public const CATEGORIES = [
        self::FAMILY,
        self::PROFESSIONAL,
        self::ORGANIZATION,
    ];

    public static function isValid(string $category): bool
    {
        return in_array($category, self::CATEGORIES, true);
    }
}