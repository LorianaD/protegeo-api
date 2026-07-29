<?php

namespace App\Enum;

final class DossierUserRole
{
    public const CURATOR_PERSON_AND_PROPERTY = 'Curateur / Curatrice à la personne et aux biens';
    public const CURATOR_PROPERTY = 'Curateur / Curatrice aux biens';
    public const CURATOR_PERSON = 'Curateur / Curatrice à la personne';
    public const GUARDIAN = 'Tuteur / Tutrice';
    public const DEPUTY_CURATOR = 'Subrogé curateur / Subrogée curatrice';
    public const DEPUTY_GUARDIAN = 'Subrogé tuteur / Subrogée tutrice';
    
    public const ROLE_TYPES = [
        self::CURATOR_PERSON_AND_PROPERTY,
        self::CURATOR_PROPERTY,
        self::CURATOR_PERSON,
        self::GUARDIAN,
        self::DEPUTY_CURATOR,
        self::DEPUTY_GUARDIAN,
    ];

    public static function isValid(string $role): bool
    {
        return in_array($role, self::ROLE_TYPES, true);
    }
}
