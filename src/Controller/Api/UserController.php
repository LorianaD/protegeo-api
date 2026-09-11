<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/user', name: 'api_user_')]
final class UserController extends AbstractController
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Returns the authenticated user's profile.
     */
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json(
            $this->userService->getProfile($user),
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Updates the authenticated user's profile.
     */
    #[Route('/profile', name: 'profile_update', methods: ['PATCH'])]
    public function profileUpdate(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Données invalides.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $updatedUser = $this->userService->updateProfile(
                $user,
                $data
            );

            return $this->json([
                'message' => 'Profil mis à jour avec succès.',
                'user' => $this->userService->getProfile($updatedUser),
            ], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Updates the authenticated user's password.
     */
    #[Route('/password', name: 'update_password', methods: ['PATCH'])]
    public function updatePassword(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Données invalides.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->userService->updatePassword(
                $user,
                $data
            );

            return $this->json([
                'message' => 'Mot de passe modifié avec succès.',
            ], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}