<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Dossier\DossierService;
use App\Service\Dossier\DossierUserService;
use App\Service\Formatter\DossierFormatter;
use App\Service\Formatter\MeasureProtectionFormatter;
use App\Service\Formatter\ProtectedPersonFormatter;
use App\Service\MeasureProtection\MeasureProtectionService;
use App\Service\ProtectedPerson\ProtectedPersonService;
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
        private DossierUserService $dossierUserService,
        private ProtectedPersonService $protectedPersonService,
        private MeasureProtectionService $measureProtectionService,
        private DossierFormatter $dossierFormatter,
        private ProtectedPersonFormatter $protectedPersonFormatter,
        private MeasureProtectionFormatter $measureProtectionFormatter,
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
            $dossiersData[] = $this->dossierFormatter->formatWithRoleType(
                $dossiersUser->getDossier(),
                $dossiersUser->getRoleType()
            );
        }

        return $this->json(
            $dossiersData,
            200
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

            if (!isset($data['protected_person']) || !is_array($data['protected_person'])) {
                throw new \InvalidArgumentException(
                    'Les informations de la personne protégée sont obligatoires.'
                );
            }

            if (!isset($data['measure_protection']) || !is_array($data['measure_protection'])) {
                throw new \InvalidArgumentException(
                    'Les informations de la mesure de protection sont obligatoires.'
                );
            }

            $dossier = $this->dossierService->createDossier($data, $user);

            $protectedPerson = $this->protectedPersonService->create(
                $dossier,
                $data['protected_person']
            );

            $measureProtection = $this->measureProtectionService->create(
                $dossier,
                $data['measure_protection']
            );

            $this->dossierService->save();

            $dossierData = $this
                ->dossierFormatter
                ->format(
                    $dossier,
                );

            $protectedPersonData = $this
                ->protectedPersonFormatter
                ->format(
                    $protectedPerson
                );

            $measureProtectionData = $this
                ->measureProtectionFormatter
                ->format(
                    $measureProtection
                );

            return $this->json([
                'message' => 'Le dossier a été créé.',
                'dossier' => $dossierData,
                'protectedPerson' => $protectedPersonData,
                'measureProtection' => $measureProtectionData,
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

        $dossierData = $this
            ->dossierFormatter
            ->format(
                $dossier
            );

        return $this->json([
            'dossier' => $dossierData,
        ], 200);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function edit(int $id, Request $request, #[CurrentUser] ?User $user) : JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $dossier = $this->dossierService->showDossier($id);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable',
                ], 404);
            }

            if (!$this->dossierUserService->userHasAccess($user, $dossier)) {
                return $this->json([
                    'message' => 'Vous n’avez pas accès à ce dossier.',
                ], 403);
            }

            $data = $request->toArray();

            $updatedDossier = $this->dossierService->updateDossier($dossier, $data);

            $this->dossierService->save();

            $dossier = $this
                ->dossierFormatter
                ->format(
                    $updatedDossier
                );

            return $this->json([
                'message' => 'Le dossier a été mis à jours.',
                'dossier' => $dossier,
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
