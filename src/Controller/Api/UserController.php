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
    public function __construct(private UserService $userService)
    {
        
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté'
            ], 401);
        }

        return $this->json(
            $this->userService->getProfile($user),
        );
    }

    #[Route('/profile', name: 'profile_update', methods: ['PATCH'])]
    public function profileUpdate(Request $request, #[CurrentUser] ?User $user) : JsonResponse
    {

        if (!$user) {
            return $this->json([
                'message' => "Utilisateur non connecté"
            ], 401);
        }

        $data = json_decode(
            $request->getContent(), 
            true
        );

        if (!$data) {
            return $this->json([
                'message' => 'Données invalides'
            ], 400);
        };

        $updatedUser = $this->userService->updateProfile($user, $data);

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $updatedUser,
        ]);
    }

    #[Route('/password', name: 'update_password', methods: ['PATCH'])]
    public function updatePassword(Request $request, #[CurrentUser] ?User $user) : JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'message' => 'Données invalides'
            ], 400);
        }

        try {

            $this->userService->updatePassword($user, $data);

        } catch (\Exception $e) {

            return $this->json([
                'message' => $e->getMessage()
            ], 400);

        }

        return $this->json([
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }
}
