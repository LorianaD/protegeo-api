<?php

namespace App\Enum;

/**
 * Defines the available roles of a user within a dossier.
 */
final class DossierUserRole
{
    public const CURATOR_PERSON_AND_PROPERTY = 'curator_person_and_property';
    public const CURATOR_PROPERTY = 'curator_property';
    public const CURATOR_PERSON = 'curator_person';
    public const GUARDIAN = 'guardian';
    public const DEPUTY_CURATOR = 'deputy_curator';
    public const DEPUTY_GUARDIAN = 'deputy_guardian';

    /**
     * List of all available dossier user roles.
     */
    public const ROLE_TYPES = [
        self::CURATOR_PERSON_AND_PROPERTY,
        self::CURATOR_PROPERTY,
        self::CURATOR_PERSON,
        self::GUARDIAN,
        self::DEPUTY_CURATOR,
        self::DEPUTY_GUARDIAN,
    ];

    /**
     * Checks whether the given role is valid.
     */
    public static function isValid(string $role): bool
    {
        return in_array($role, self::ROLE_TYPES, true);
    }
}