<?php

namespace App\Service\Validator;

class PasswordValidator
{
    public function validate(string $password): void
    {
        if (strlen($password) < 12) {
            throw new \InvalidArgumentException(
                "Le mot de passe doit contenir au moins 12 caractères."
            );
        }

        if (!preg_match('/\d/', $password)) {
            throw new \InvalidArgumentException(
                "Le mot de passe doit contenir au moins un chiffre."
            );
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            throw new \InvalidArgumentException(
                "Le mot de passe doit contenir au moins un caractère spécial."
            );
        }

    }
}