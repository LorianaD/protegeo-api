<?php

namespace App\Enum;

final class MeasureProtectionType
{
    public const SAFEGUARD_OF_JUSTICE = 'Sauvegarde de justice';
    public const SIMPLE_CURATORSHIP = 'Curatelle simple';
    public const REINFORCED_CURATORSHIP = 'Curatelle renforcée';
    public const ADAPTED_CURATORSHIP = 'Curatelle aménagée';
    public const GUARDIANSHIP = 'Tutelle';
    public const FAMILY_AUTHORIZATION = 'Habilitation familiale';
    public const FUTURE_PROTECTION_MANDATE = 'Mandat de protection future';

    public const TYPES = [
        self::SAFEGUARD_OF_JUSTICE,
        self::SIMPLE_CURATORSHIP,
        self::REINFORCED_CURATORSHIP,
        self::ADAPTED_CURATORSHIP,
        self::GUARDIANSHIP,
        self::FAMILY_AUTHORIZATION,
        self::FUTURE_PROTECTION_MANDATE,
    ];

    public static function isValid(string $measureTypes): bool
    {
        return in_array($measureTypes, self::TYPES, true);
    }
}
