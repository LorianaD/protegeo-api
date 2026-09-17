<?php

namespace App\Controller\Api;

use App\Entity\DossierUser;
use App\Service\Dossier\DossierService;
use App\Service\Dossier\DossierUserService;
use App\Service\Formatter\DossierFormatter;
use App\Service\Formatter\MeasureProtectionFormatter;
use App\Service\Formatter\ProtectedPersonFormatter;
use App\Service\ManagementAccount\ManagementAccountService;
use App\Service\MeasureProtection\MeasureProtectionService;
use App\Service\ProtectedPerson\ProtectedPersonService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers', name: 'api_dossiers_')]
final class DossierController extends ApiController
{
    public function __construct(
        private DossierService $dossierService,
        private DossierUserService $dossierUserService,
        private ProtectedPersonService $protectedPersonService,
        private MeasureProtectionService $measureProtectionService,
        private ManagementAccountService $managementAccountService,
        private DossierFormatter $dossierFormatter,
        private ProtectedPersonFormatter $protectedPersonFormatter,
        private MeasureProtectionFormatter $measureProtectionFormatter,
    ) {}

    /**
     * Returns all open dossiers accessible to the authenticated user.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossierUsers = $this->dossierUserService->getOpenDossiersByUser(
                $user
            );

            $dossiersData = [];

            foreach ($dossierUsers as $dossierUser) {
                $dossier = $dossierUser->getDossier();

                $dossierData = $this->dossierFormatter->formatForUserList(
                    $dossier,
                    $dossierUser->getRoleType()
                );

                try {
                    $measureProtection = $this
                        ->measureProtectionService
                        ->getLatestByDossierId(
                            $dossier->getId(),
                            $user
                        );

                    $dossierData['measure'] = $this
                        ->measureProtectionFormatter
                        ->format($measureProtection);
                } catch (\RuntimeException) {
                    $dossierData['measure'] = null;
                }

                $dossiersData[] = $dossierData;
            }

            return $this->json(
                $dossiersData,
                JsonResponse::HTTP_OK
            );
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Creates a dossier with its protected person, protection measure
     * and initial management account.
     */
    #[Route('', name: 'new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $data = $request->toArray();

            $hasProtectedPerson = isset($data['protected_person']) && is_array($data['protected_person']);

            if (!$hasProtectedPerson) {
                throw new \InvalidArgumentException(
                    'Les informations de la personne protégée sont obligatoires.'
                );
            }

            $hasMeasureProtection = isset($data['measure_protection']) && is_array($data['measure_protection']);

            if (!$hasMeasureProtection) {
                throw new \InvalidArgumentException(
                    'Les informations de la mesure de protection sont obligatoires.'
                );
            }

            $dossier = $this->dossierService->createDossier(
                $data,
                $user
            );

            $protectedPerson = $this->protectedPersonService->create(
                $dossier,
                $data['protected_person']
            );

            $measureProtection = $this->measureProtectionService->create(
                $dossier,
                $data['measure_protection']
            );

            $this->managementAccountService->createInitial($dossier, $measureProtection);

            $this->dossierService->save();

            $dossierData = $this->dossierFormatter->format($dossier);
            $protectedPersonData = $this->protectedPersonFormatter->format($protectedPerson);
            $measureProtectionData = $this->measureProtectionFormatter->format($measureProtection);

            return $this->json([
                'message' => 'Le dossier a été créé.',
                'dossier' => $dossierData,
                'protected_person' => $protectedPersonData,
                'measure_protection' => $measureProtectionData,
            ], JsonResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Returns available dossier role types.
     */
    #[Route('/role-types', name: 'role_types', methods: ['GET'])]
    public function roleTypes(): JsonResponse
    {
        $roleTypes = $this->dossierUserService->getRoleType();

        return $this->json([
            'role_types' => $roleTypes,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Returns one dossier when the authenticated user has access to it.
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierService->showDossier($id);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $hasAccess = $this->dossierUserService->userHasAccess($user, $dossier);

            if (!$hasAccess) {
                return $this->json([
                    'message' => 'Vous n’avez pas accès à ce dossier.',
                ], JsonResponse::HTTP_FORBIDDEN);
            }

            $dossierData = $this->dossierFormatter->format($dossier);

            return $this->json([
                'dossier' => $dossierData,
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Partially updates an existing dossier.
     */
    #[Route('/{id}', name: 'edit', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierService->showDossier($id);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            if (!$this->dossierUserService->userHasAccess($user, $dossier)) {
                return $this->json([
                    'message' => 'Vous n’avez pas accès à ce dossier.',
                ], JsonResponse::HTTP_FORBIDDEN);
            }

            $data = $request->toArray();

            $updatedDossier = $this->dossierService->updateDossier(
                $dossier,
                $data
            );

            $this->dossierService->save();

            $dossierData = $this->dossierFormatter->format($updatedDossier);

            return $this->json([
                'message' => 'Le dossier a été mis à jour.',
                'dossier' => $dossierData,
            ], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Associates the authenticated user with an existing dossier.
     */
    #[Route('/{id}/users', name: 'add_current_user', methods: ['POST'])]
    public function addCurrentUser(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierService->showDossier($id);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $data = $request->toArray();

            $roleType = $data['role_type'] ?? null;

            if (!$roleType) {
                return $this->json([
                    'message' => 'Le rôle dans le dossier est obligatoire.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $dossierUser = $this->dossierUserService->addUserToDossier(
                $dossier,
                $user,
                $roleType
            );

            $dossierUserData = $this->formatDossierUser($dossierUser);

            return $this->json([
                'message' => 'L’utilisateur a été associé au dossier.',
                'dossier_user' => $dossierUserData,
            ], JsonResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Returns a dossier from its reference number when the user has access.
     */
    #[Route('/reference/{referenceNumber}', name: 'show_by_reference', methods: ['GET'])]
    public function showByReference(string $referenceNumber): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierService->getByReferenceNumberAndUser(
                $referenceNumber,
                $user
            );

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            return $this->json([
                'id' => $dossier->getId(),
                'reference_number' => $dossier->getReferenceNumber(),
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Formats a dossier-user relation for the API response.
     */
    private function formatDossierUser(DossierUser $dossierUser): array
    {
        return [
            'id' => $dossierUser->getId(),
            'user_id' => $dossierUser->getUser()->getId(),
            'dossier_id' => $dossierUser->getDossier()->getId(),
            'role_type' => $dossierUser->getRoleType(),
        ];
    }
}