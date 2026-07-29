<?php

namespace App\Controller\Api;

use App\Entity\ManagementAccount;
use App\Enum\ManagementAccountStatus;
use App\Repository\DossierRepository;
use App\Repository\ManagementAccountRepository;
use App\Service\ManagementAccount\ManagementAccountService;
use DateTimeImmutable;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{dossierId}/management-accounts')]
class ManagementAccountController extends AbstractController
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private ManagementAccountRepository $managementAccountRepository,
        private ManagementAccountService $managementAccountService,
    ) {
    }

    /**
     * Returns all management accounts for the given dossier.
     */
    #[Route('', name: 'api_management_accounts_index', methods: ['GET'])]
    public function index(int $dossierId): JsonResponse
    {
        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json([
                'message' => 'Dossier introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $managementAccounts = $this->managementAccountService
            ->getManagementAccountsByDossier($dossier);

        $data = [];

        foreach ($managementAccounts as $managementAccount) {
            $data[] = $this->formatManagementAccount($managementAccount);
        }

        return $this->json($data);
    }

    /**
     * Returns one management account.
     */
    #[Route('/{managementAccountId}', name: 'api_management_accounts_show', methods: ['GET'])]
    public function show(int $dossierId, int $managementAccountId): JsonResponse
    {
        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json([
                'message' => 'Dossier introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $managementAccount = $this->managementAccountRepository->find($managementAccountId);

        if (
            !$managementAccount
            || $managementAccount->getDossier()->getId() !== $dossier->getId()
        ) {
            return $this->json([
                'message' => 'Compte de gestion introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json(
            $this->formatManagementAccount($managementAccount)
        );
    }

    /**
     * Creates a new management account.
     */
    #[Route('', name: 'api_management_accounts_create', methods: ['POST'])]
    public function create(Request $request, int $dossierId): JsonResponse
    {
        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json([
                'message' => 'Dossier introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Les données JSON sont invalides.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!isset($data['year'])) {
            return $this->json([
                'message' => 'L’année est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $year = (int) $data['year'];

        if ($year < 1900 || $year > 2100) {
            return $this->json([
                'message' => 'L’année est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existingManagementAccount = $this->managementAccountService
            ->getManagementAccountByYear($dossier, $year);

        if ($existingManagementAccount) {
            return $this->json([
                'message' => 'Un compte de gestion existe déjà pour cette année.',
            ], JsonResponse::HTTP_CONFLICT);
        }

        $managementAccount = new ManagementAccount();
        $status = $data['status'] ?? ManagementAccountStatus::IN_PROGRESS;

        if (!is_string($status) || !ManagementAccountStatus::isValid($status)) {
            return $this->json([
                'message' => 'Le statut est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $managementAccount->setDossier($dossier);
        $managementAccount->setYear(new DateTime($year . '-01-01'));
        $managementAccount->setStatus($status);
        $managementAccount->setNote($data['note'] ?? null);

        if (!empty($data['sent_at'])) {
            $managementAccount->setSentAt(
                new DateTimeImmutable($data['sent_at'])
            );
        }

        $managementAccount = $this->managementAccountService
            ->createManagementAccount($managementAccount);

        return $this->json(
            $this->formatManagementAccount($managementAccount),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Updates an existing management account.
     */
    #[Route('/{managementAccountId}', name: 'api_management_accounts_update', methods: ['PATCH'])]
    public function update(Request $request, int $dossierId, int $managementAccountId): JsonResponse
    {
        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json([
                'message' => 'Dossier introuvable.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $managementAccount = $this->managementAccountRepository->find($managementAccountId);

        if (
            !$managementAccount
            || $managementAccount->getDossier()->getId() !== $dossier->getId()
        ) {
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

        if (array_key_exists('status', $data)) {
            $status = $data['status'];

            if (!is_string($status) || !ManagementAccountStatus::isValid($status)) {
                return $this->json([
                    'message' => 'Le statut est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $managementAccount->setStatus($status);
        }

        if (array_key_exists('note', $data)) {
            $managementAccount->setNote($data['note']);
        }

        if (array_key_exists('sent_at', $data)) {
            $sentAt = $data['sent_at']
                ? new DateTimeImmutable($data['sent_at'])
                : null;

            $managementAccount->setSentAt($sentAt);
        }

        $this->managementAccountService->updateManagementAccount($managementAccount);

        return $this->json(
            $this->formatManagementAccount($managementAccount)
        );
    }

    /**
     * Formats a management account for the JSON response.
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