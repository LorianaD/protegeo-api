<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Dossier\DossierService;
use App\Service\Dossier\DossierUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/dossiers', name: 'api_dossiers_')]
final class DossierController extends AbstractController
{

    public function __construct(
        private DossierService $dossierService,
        private DossierUserService $dossierUserService
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        $dossiersUsers = $this->dossierUserService->getOpenDossiersByUser($user);

        $dossiersData = [];

        foreach ($dossiersUsers as $dossiersUser) {
            $dossier = $dossiersUser->getDossier();

            $dossiersData[] = [
                'id' => $dossier->getId(),
                'referenceNumber' => $dossier->getReferenceNumber(),
                'openedAt' => $dossier->getOpenedAt()?->format('Y-m-d'),
                'closedAt' => $dossier->getClosedAt()?->format('Y-m-d'),
                'roleType' => $dossiersUser->getRoleType(),
            ];
        }

        return $this->json(
            $dossiersData,
        );
    }

    #[Route('', name: 'new', methods: ['POST'])]
    public function new(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $data = $request->toArray();

            $dossier = $this->dossierService->createDossier($data, $user);

            return $this->json([
                'message' => 'Le dossier a été créé.',
                'dossier' => [
                    'id' => $dossier->getId(),
                    'referenceNumber' => $dossier->getReferenceNumber(),
                    'openedAt' => $dossier->getOpenedAt()?->format('Y-m-d'),
                    'closedAt' => $dossier->getClosedAt()?->format('Y-m-d'),
                ],
            ], 201);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

    }

    #[Route('/role-types', name: 'role_types', methods: ['GET'])]
    public function roleTypes() : JsonResponse
    {
        $roleType = $this->dossierUserService->getRoleType();

        return $this->json([
            'roleType' => $roleType,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements:['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, #[CurrentUser] ?User $user) : JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        $dossier = $this->dossierService->showDossier($id);

        if (!$dossier) {
            return $this->json([
                'message' => 'Dossier introuvable',
            ], 404);
        }

        $hasAccess = $this->dossierUserService->userHasAccess($user, $dossier);

        if (!$hasAccess) {
            return $this->json([
                'message' => 'Vous n’avez pas accès à ce dossier.',
            ], 403);
        }

        $id = $dossier->getId();
        $referenceNumber = $dossier->getReferenceNumber();
        $openedAt = $dossier->getOpenedAt()?->format('Y-m-d');
        $closedAt = $dossier->getClosedAt()?->format('Y-m-d');

        return $this->json([
            'id' => $id,
            'referenceNumber' => $referenceNumber,
            'openedAt' => $openedAt,
            'closedAt' => $closedAt,
        ]);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function edit(int $id, Request $request) : JsonResponse
    {
        try {
            $dossier = $this->dossierService->showDossier($id);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable',
                ], 404);
            }

            $data = $request->toArray();

            $updatedDossier = $this->dossierService->updateDossier($dossier, $data);

            $id = $updatedDossier->getId();
            $referenceNumber = $updatedDossier->getReferenceNumber();
            $openedAt = $updatedDossier->getOpenedAt()?->format('Y-m-d');
            $closedAt = $updatedDossier->getClosedAt()?->format('Y-m-d');

            return $this->json([
                    'message' => 'Le dossier a été mis à jours.',
                    'dossier' => [
                        'id' => $id,
                        'referenceNumber' => $referenceNumber,
                        'openedAt' => $openedAt,
                        'closedAt' => $closedAt,
                    ],
            ], 200);

        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

    #[Route('/{id}/users', name: 'add_current_user', methods: ['POST'])]
    public function addCurrentUser(int $id, Request $request, #[CurrentUser] ?User $user) : JsonResponse 
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        $dossier = $this->dossierService->showDossier($id);

        if (!$dossier) {
            return $this->json([
                'message' => 'Dossier introuvable.',
            ], 404);
        }

        try {
            $data = $request->toArray();

            $roleType = $data['roleType'] ?? null;

            if (!$roleType) {
                return $this->json([
                    'message' => 'Le rôle dans le dossier est obligatoire.',
                ], 400);
            }

            $dossierUser = $this->dossierUserService->addUserToDossier(
                $dossier,
                $user,
                $roleType
            );

            return $this->json([
                'message' => 'L’utilisateur a été associé au dossier.',
                'dossierUser' => [
                    'id' => $dossierUser->getId(),
                    'userId' => $user->getId(),
                    'dossierId' => $dossier->getId(),
                    'roleType' => $dossierUser->getRoleType(),
                ]
            ], 201);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }

}
