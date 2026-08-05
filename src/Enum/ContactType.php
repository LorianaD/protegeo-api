<?php

namespace App\Enum;

/**
 * Defines all available contact types used in the application.
 *
 * Contact types are used to:
 * - validate incoming API data;
 * - classify contacts by category;
 * - display contacts in the appropriate dashboard section
 *   (Family, Professional Contacts, Useful Contacts).
 *
 * Example:
 * - father
 * - doctor
 * - caf
 */
final class ContactType
{
    // -------------------------------------------------------------------------
    // FAMILY
    // -------------------------------------------------------------------------

    /** Parent (father). */
    public const FATHER = 'father';

    /** Parent (mother). */
    public const MOTHER = 'mother';

    /** Husband, wife or partner. */
    public const SPOUSE = 'spouse';

    /** Brother or sister. */
    public const SIBLING = 'sibling';

    /** Son or daughter. */
    public const CHILD = 'child';

    /** Trusted person designated by the protected person. */
    public const TRUSTED_PERSON = 'trusted_person';

    /** Deputy guardian or deputy curator. */
    public const DEPUTY_GUARDIAN = 'deputy_guardian';

    // -------------------------------------------------------------------------
    // PROFESSIONALS
    // -------------------------------------------------------------------------

    /** General practitioner or specialist. */
    public const DOCTOR = 'doctor';

    /** Social worker or social support professional. */
    public const SOCIAL_WORKER = 'social_worker';

    /** Professional guardian or professional curator. */
    public const PROFESSIONAL_GUARDIAN = 'professional_guardian';

    /** Bank advisor. */
    public const BANK_ADVISOR = 'bank_advisor';

    /** Lawyer. */
    public const LAWYER = 'lawyer';

    /** Notary. */
    public const NOTARY = 'notary';

    // -------------------------------------------------------------------------
    // ORGANIZATIONS
    // -------------------------------------------------------------------------

    /** Family allowance office. */
    public const CAF = 'caf';

    /** Health insurance office. */
    public const CPAM = 'cpam';

    /** Disability support office. */
    public const MDPH = 'mdph';

    /** Banking institution. */
    public const BANK = 'bank';

    /** Medical practice. */
    public const TAX_OFFICE = 'tax_office';

    /** Any other public or private organization. */
    public const OTHER_ORGANIZATION = 'other_organization';

    /**
     * List of all supported contact types.
     */
    public const TYPES = [

        // FAMILY
        self::FATHER,
        self::MOTHER,
        self::SPOUSE,
        self::SIBLING,
        self::CHILD,
        self::TRUSTED_PERSON,
        self::DEPUTY_GUARDIAN,

        // PROFESSIONALS
        self::DOCTOR,
        self::SOCIAL_WORKER,
        self::PROFESSIONAL_GUARDIAN,
        self::BANK_ADVISOR,
        self::LAWYER,
        self::NOTARY,

        // ORGANIZATIONS
        self::CAF,
        self::CPAM,
        self::MDPH,
        self::BANK,
        self::TAX_OFFICE,
        self::OTHER_ORGANIZATION,
    ];

    /**
     * Checks whether the given contact type is supported.
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }
}