<?php

namespace App\Controller\Api;

use App\Entity\ManagementAccount;
use App\Enum\ManagementAccountStatus;
use App\Repository\DossierRepository;
use App\Repository\ManagementAccountRepository;
use App\Service\ManagementAccount\ManagementAccountService;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{dossierId}/management-accounts')]
class ManagementAccountController extends ApiController
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private ManagementAccountRepository $managementAccountRepository,
        private ManagementAccountService $managementAccountService,
    ) {}

    /**
     * Returns all management accounts for the given dossier.
     */
    #[Route('', name: 'api_management_accounts_index', methods: ['GET'])]
    public function index(int $dossierId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable ou accès refusé.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $managementAccounts = $this->managementAccountService->getManagementAccountsByDossier($dossier);

            $managementAccountsData = [];

            foreach ($managementAccounts as $managementAccount) {
                $managementAccountsData[] = $this->formatManagementAccount($managementAccount);
            }

            return $this->json(
                $managementAccountsData,
                JsonResponse::HTTP_OK
            );
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Returns one management account.
     */
    #[Route('/{managementAccountId}', name: 'api_management_accounts_show', methods: ['GET'])]
    public function show(int $dossierId, int $managementAccountId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable ou accès refusé.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $managementAccount = $this->managementAccountRepository->findOneByIdAndDossierId(
                $managementAccountId,
                $dossierId
            );

            if (!$managementAccount) {
                return $this->json([
                    'message' => 'Compte de gestion introuvable.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $managementAccountData = $this->formatManagementAccount($managementAccount);

            return $this->json(
                $managementAccountData,
                JsonResponse::HTTP_OK
            );
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Creates a new management account.
     */
    #[Route('', name: 'api_management_accounts_create', methods: ['POST'])]
    public function create(Request $request, int $dossierId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable ou accès refusé.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return $this->json([
                    'message' => 'Les données JSON sont invalides.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $hasYear = isset($data['year']);

            if (!$hasYear) {
                return $this->json([
                    'message' => 'L’année est obligatoire.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $year = (int) $data['year'];
            $hasValidYear = $year >= 1900 && $year <= 2100;

            if (!$hasValidYear) {
                return $this->json([
                    'message' => 'L’année est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $existingManagementAccount = $this->managementAccountService->getManagementAccountByYear(
                $dossier,
                $year
            );

            if ($existingManagementAccount) {
                return $this->json([
                    'message' => 'Un compte de gestion existe déjà pour cette année.',
                ], JsonResponse::HTTP_CONFLICT);
            }

            $status = $data['status'] ?? ManagementAccountStatus::IN_PROGRESS;
            $hasValidStatus = is_string($status) && ManagementAccountStatus::isValid($status);

            if (!$hasValidStatus) {
                return $this->json([
                    'message' => 'Le statut est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $sentAt = null;

            if (!empty($data['sent_at'])) {
                $sentAt = new DateTimeImmutable($data['sent_at']);
            }

            $managementAccount = new ManagementAccount();
            $managementAccount->setDossier($dossier);
            $managementAccount->setYear(new DateTime($year . '-01-01'));
            $managementAccount->setNote($data['note'] ?? null);

            $this->managementAccountService->applyStatus(
                $managementAccount,
                $status,
                $sentAt
            );

            $managementAccount = $this->managementAccountService->createManagementAccount($managementAccount);

            $managementAccountData = $this->formatManagementAccount($managementAccount);

            return $this->json(
                $managementAccountData,
                JsonResponse::HTTP_CREATED
            );
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
     * Updates an existing management account.
     */
    #[Route('/{managementAccountId}', name: 'api_management_accounts_update', methods: ['PATCH'])]
    public function update(Request $request, int $dossierId, int $managementAccountId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable ou accès refusé.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $managementAccount = $this->managementAccountRepository->findOneByIdAndDossierId(
                $managementAccountId,
                $dossierId
            );

            if (!$managementAccount) {
                return $this->json([
                    'message' => 'Compte de gestion introuvable.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return $this->json([
                    'message' => 'Les données JSON sont invalides.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (array_key_exists('note', $data)) {
                $managementAccount->setNote($data['note']);
            }

            if (array_key_exists('status', $data)) {
                $status = $data['status'];
                $hasValidStatus = is_string($status) && ManagementAccountStatus::isValid($status);

                if (!$hasValidStatus) {
                    return $this->json([
                        'message' => 'Le statut est invalide.',
                    ], JsonResponse::HTTP_BAD_REQUEST);
                }

                $sentAt = null;

                if (!empty($data['sent_at'])) {
                    $sentAt = new DateTimeImmutable($data['sent_at']);
                }

                $this->managementAccountService->applyStatus(
                    $managementAccount,
                    $status,
                    $sentAt
                );
            }

            $this->managementAccountService->updateManagementAccount($managementAccount);

            $managementAccountData = $this->formatManagementAccount($managementAccount);

            return $this->json(
                $managementAccountData,
                JsonResponse::HTTP_OK
            );
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
     * Formats a management account for the API response.
     */
    private function formatManagementAccount(ManagementAccount $managementAccount): array
    {
        return [
            'id' => $managementAccount->getId(),
            'dossier_id' => $managementAccount->getDossier()->getId(),
            'year' => $managementAccount->getYear()?->format('Y'),
            'status' => $managementAccount->getStatus(),
            'sent_at' => $managementAccount->getSentAt()?->format('Y-m-d H:i:s'),
            'note' => $managementAccount->getNote(),
            'created_at' => $managementAccount->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $managementAccount->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}