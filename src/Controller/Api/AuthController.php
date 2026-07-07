<?php

namespace App\Controller\Api;

use App\Service\Auth\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth_')]
final class AuthController extends AbstractController
{

    public function __construct(private AuthService $authService)
    {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                "message" => "Données invalides"
            ], 400);
        }

        try {

            $user = $this->authService->register($data);

            return $this->json([
                'message' => 'Compte créé avec succès',
                'user' => $user,
            ], 201);

        } catch (\InvalidArgumentException $e) {

            return $this->json([
                'message' => $e->getMessage(),
            ], 400);

        }

    }
}
