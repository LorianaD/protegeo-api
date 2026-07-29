<?php

namespace App\Enum;

final class ManagementAccountStatus
{
    public const IN_PROGRESS = 'En cours';
    public const TO_VALIDATE = 'À valider';
    public const VALIDATED = 'Validé';
    public const SENT = 'Envoyé';

    public const STATUS_TYPES = [
        self::IN_PROGRESS,
        self::TO_VALIDATE,
        self::VALIDATED,
        self::SENT,
    ];

    /**
     * Checks if the status is valid.
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::STATUS_TYPES, true);
    }
}