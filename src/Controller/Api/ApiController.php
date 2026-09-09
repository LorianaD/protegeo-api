<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class ApiController extends AbstractController
{
    /**
     * Returns the currently authenticated application user.
     *
     * @throws \RuntimeException When no valid User instance is authenticated.
     */
    protected function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \RuntimeException(
                'Utilisateur non authentifié.'
            );
        }

        return $user;
    }
}