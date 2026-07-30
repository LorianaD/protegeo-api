<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Repository\DossierRepository;
use App\Service\BankAccount\BankAccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{dossierId}/bank-accounts', name: 'api_bank_accounts_')]
class BankAccountController extends AbstractController
{
    public function __construct(
        private BankAccountService $bankAccountService,
        private DossierRepository $dossierRepository
    ) {}

    /**
     * Returns all bank accounts associated with a dossier.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $dossierId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['error' => 'Utilisateur non authentifié.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

        if (!$dossier) {
            return $this->json(
                ['error' => 'Dossier introuvable ou accès refusé.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $bankAccounts = $this->bankAccountService->getByDossierId($dossierId);

        return $this->json(
            array_map(
                fn (BankAccount $bankAccount): array => $this->formatBankAccount($bankAccount),
                $bankAccounts
            ),
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Returns one bank account associated with a dossier.
     */
    #[Route('/{bankAccountId}', name: 'show', methods: ['GET'])]
    public function show(int $dossierId, int $bankAccountId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['error' => 'Utilisateur non authentifié.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

        if (!$dossier) {
            return $this->json(
                ['error' => 'Dossier introuvable ou accès refusé.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $bankAccount = $this->bankAccountService->getByIdAndDossierId($bankAccountId, $dossierId);

        if (!$bankAccount) {
            return $this->json(
                ['error' => 'Compte bancaire introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $this->json(
            $this->formatBankAccount($bankAccount),
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Creates a bank account associated with a dossier.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $dossierId, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['error' => 'Utilisateur non authentifié.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

        if (!$dossier) {
            return $this->json(
                ['error' => 'Dossier introuvable ou accès refusé.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Les données envoyées sont invalides.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $bankAccount = $this->bankAccountService->create($dossier, $data);

            return $this->json(
                $this->formatBankAccount($bankAccount),
                JsonResponse::HTTP_CREATED
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(
                ['error' => $exception->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Updates an existing bank account.
     */
    #[Route('/{bankAccountId}', name: 'update', methods: ['PATCH'])]
    public function update(int $dossierId, int $bankAccountId, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['error' => 'Utilisateur non authentifié.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $dossier = $this->dossierRepository->findOneByIdAndUser($dossierId, $user);

        if (!$dossier) {
            return $this->json(
                ['error' => 'Dossier introuvable ou accès refusé.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $bankAccount = $this->bankAccountService->getByIdAndDossierId($bankAccountId, $dossierId);

        if (!$bankAccount) {
            return $this->json(
                ['error' => 'Compte bancaire introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Les données envoyées sont invalides.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $bankAccount = $this->bankAccountService->update($bankAccount, $data);

            return $this->json(
                $this->formatBankAccount($bankAccount),
                JsonResponse::HTTP_OK
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(
                ['error' => $exception->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Formats a bank account for the API response.
     */
    private function formatBankAccount(BankAccount $bankAccount): array
    {
        return [
            'id' => $bankAccount->getId(),
            'bank_name' => $bankAccount->getBankName(),
            'agency_name' => $bankAccount->getAgencyName(),
            'account_type' => $bankAccount->getAccountType(),
            'account_label' => $bankAccount->getAccountLabel(),
            'account_number_masked' => $bankAccount->getAccountNumberMasked(),
            'iban_masked' => $bankAccount->getIbanMasked(),
            'bic' => $bankAccount->getBic(),
            'opened_at' => $bankAccount->getOpenedAt()?->format('Y-m-d'),
            'closed_at' => $bankAccount->getClosedAt()?->format('Y-m-d'),
            'validated_at' => $bankAccount->getValidatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}