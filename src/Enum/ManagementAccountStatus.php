<?php

namespace App\Enum;

/**
 * Defines the available statuses for a management account.
 */
final class ManagementAccountStatus
{
    public const IN_PROGRESS = 'in_progress';
    public const TO_VALIDATE = 'to_validate';
    public const VALIDATED = 'validated';
    public const SENT = 'sent';

    /**
     * List of all available management account statuses.
     */
    public const STATUS_TYPES = [
        self::IN_PROGRESS,
        self::TO_VALIDATE,
        self::VALIDATED,
        self::SENT,
    ];

    /**
     * Checks whether the given status is valid.
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::STATUS_TYPES, true);
    }
}