<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Formatter\ProtectedPersonFormatter;
use App\Service\ProtectedPerson\ProtectedPersonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/dossiers/{id}/protected-person', name: 'api_protected_person_')]
final class ProtectedPersonController extends AbstractController
{
    public function __construct(
        private readonly ProtectedPersonService $protectedPersonService,
        private readonly ProtectedPersonFormatter $protectedPersonFormatter,
    ){}

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $protectedPerson = $this->protectedPersonService
                ->getByDossierId(
                    $id,
                    $user
                );

            return $this->json([
                'protectedPerson' => $this->protectedPersonFormatter->format(
                    $protectedPerson
                ),
            ]);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    #[Route('', name: 'edit', methods: ['PATCH'])]
    public function edit(int $id, Request $request, #[CurrentUser] ?User $user) : JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $protectedPerson = $this->protectedPersonService
                ->getByDossierId(
                    $id,
                    $user
                );

            $data = $request->toArray();

            $updatedProtectedPerson = $this->protectedPersonService
                ->update(
                    $protectedPerson,
                    $data
                );

            return $this->json([
                'message' => 'La personne protégée a été mise à jour.',
                'protectedPerson' => $this->protectedPersonFormatter->format(
                    $updatedProtectedPerson
                ),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 404);
        }
    }
}
