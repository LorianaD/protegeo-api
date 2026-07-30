<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Entity\User;
use App\Repository\DossierRepository;
use App\Service\BankAccount\BankAccountService;
use App\Service\Dossier\DossierUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{dossierId}/bank-accounts', name: 'api_bank_accounts_')]
class BankAccountController extends AbstractController
{
    public function __construct(
        private BankAccountService $bankAccountService,
        private DossierRepository $dossierRepository,
        private DossierUserService $dossierUserService
    ) {}

    /**
     * Returns all bank accounts for a dossier.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $dossierId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json(
                ['message' => 'Dossier introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if (!$this->dossierUserService->userHasAccess($user, $dossier)) {
            return $this->json(
                ['message' => 'Accès refusé à ce dossier.'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $bankAccounts = $this->bankAccountService->getByDossierId(
            $dossierId,
            $user
        );

        return $this->json(
            array_map(
                fn ($bankAccount) => $this->formatBankAccount($bankAccount),
                $bankAccounts
            )
        );
    }

    /**
     * Returns one bank account for a dossier.
     */
    #[Route('/{bankAccountId}', name: 'show', methods: ['GET'])]
    public function show(int $dossierId, int $bankAccountId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json(
                ['message' => 'Dossier introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if (!$this->dossierUserService->userHasAccess($user, $dossier)) {
            return $this->json(
                ['message' => 'Accès refusé à ce dossier.'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $bankAccount = $this->bankAccountService->getByIdAndDossierId(
            $bankAccountId,
            $dossierId,
            $user
        );

        if (!$bankAccount) {
            return $this->json(
                ['message' => 'Compte bancaire introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $this->json(
            $this->formatBankAccount($bankAccount)
        );
    }

    /**
     * Creates a bank account for a dossier.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $dossierId, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json(
                ['message' => 'Dossier introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if (!$this->dossierUserService->userHasAccess($user, $dossier)) {
            return $this->json(
                ['message' => 'Accès refusé à ce dossier.'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        try {
            $bankAccount = $this->bankAccountService->create(
                $dossier,
                $request->toArray()
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(
                ['message' => $exception->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return $this->json(
            $this->formatBankAccount($bankAccount),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Updates a bank account.
     */
    #[Route('/{bankAccountId}', name: 'update', methods: ['PATCH'])]
    public function update(int $dossierId, int $bankAccountId, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json(
                ['message' => 'Dossier introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if (!$this->dossierUserService->userHasAccess($user, $dossier)) {
            return $this->json(
                ['message' => 'Accès refusé à ce dossier.'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $bankAccount = $this->bankAccountService->getByIdAndDossierId(
            $bankAccountId,
            $dossierId,
            $user
        );

        if (!$bankAccount) {
            return $this->json(
                ['message' => 'Compte bancaire introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        try {
            $bankAccount = $this->bankAccountService->update(
                $bankAccount,
                $request->toArray()
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(
                ['message' => $exception->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return $this->json(
            $this->formatBankAccount($bankAccount)
        );
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
            'validated_at' => $bankAccount->getValidatedAt()?->format(
                'Y-m-d H:i:s'
            ),
        ];
    }
}