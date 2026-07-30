<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Entity\BankingTransaction;
use App\Repository\BankAccountRepository;
use App\Repository\BankingTransactionRepository;
use App\Repository\DossierRepository;
use App\Service\BankingTransaction\BankingTransactionService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{dossierId}/banking-transactions', name: 'api_banking_transactions_')]
class BankingTransactionController extends AbstractController
{
    public function __construct(
        private BankingTransactionService $bankingTransactionService,
        private BankingTransactionRepository $bankingTransactionRepository,
        private BankAccountRepository $bankAccountRepository,
        private DossierRepository $dossierRepository
    ) {}

    /**
     * Returns all banking transactions associated with a dossier.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $dossierId, Request $request): JsonResponse
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

        try {
            $bankAccountId = $request->query->get('bank_account_id');
            $startDateValue = $request->query->get('start_date');
            $endDateValue = $request->query->get('end_date');

            $hasBankAccountFilter = $bankAccountId !== null;
            $hasStartDate = $startDateValue !== null;
            $hasEndDate = $endDateValue !== null;
            $hasPeriodFilter = $hasStartDate || $hasEndDate;

            if ($hasBankAccountFilter) {
                $bankAccount = $this->bankAccountRepository->findOneByIdAndDossierId((int) $bankAccountId, $dossierId);

                if (!$bankAccount) {
                    return $this->json(
                        ['error' => 'Compte bancaire introuvable.'],
                        JsonResponse::HTTP_NOT_FOUND
                    );
                }

                $bankingTransactions = $this->bankingTransactionService->getByBankAccountId($bankAccount->getId());
            } elseif ($hasPeriodFilter) {
                $hasCompletePeriod = $hasStartDate && $hasEndDate;

                if (!$hasCompletePeriod) {
                    return $this->json(
                        ['error' => 'Les dates de début et de fin sont obligatoires pour filtrer par période.'],
                        JsonResponse::HTTP_BAD_REQUEST
                    );
                }

                $startDate = $this->createDateFromValue($startDateValue);
                $endDate = $this->createDateFromValue($endDateValue);

                $bankingTransactions = $this->bankingTransactionService->getByDossierIdAndPeriod($dossierId, $startDate, $endDate);
            } else {
                $bankingTransactions = $this->bankingTransactionService->getByDossierId($dossierId);
            }

            return $this->json(
                array_map(
                    fn (BankingTransaction $bankingTransaction): array => $this->formatBankingTransaction($bankingTransaction),
                    $bankingTransactions
                ),
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
     * Returns one banking transaction.
     */
    #[Route('/{bankingTransactionId}', name: 'show', methods: ['GET'])]
    public function show(int $dossierId, int $bankingTransactionId): JsonResponse
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

        $bankingTransaction = $this->bankingTransactionRepository->getOneByIdAndDossierId($bankingTransactionId, $dossierId);

        if (!$bankingTransaction) {
            return $this->json(
                ['error' => 'Mouvement bancaire introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $this->json(
            $this->formatBankingTransaction($bankingTransaction),
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Creates a banking transaction.
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

        $sourceBankAccountId = $data['source_bank_account_id'] ?? null;
        $destinationBankAccountId = $data['destination_bank_account_id'] ?? null;
        $hasSourceBankAccountId = filter_var($sourceBankAccountId, FILTER_VALIDATE_INT) !== false;
        $hasDestinationBankAccountId = filter_var($destinationBankAccountId, FILTER_VALIDATE_INT) !== false;

        if (!$hasSourceBankAccountId) {
            return $this->json(
                ['error' => 'Le compte bancaire source est obligatoire.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        if (!$hasDestinationBankAccountId) {
            return $this->json(
                ['error' => 'Le compte bancaire destinataire est obligatoire.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $sourceBankAccount = $this->bankAccountRepository->findOneByIdAndDossierId((int) $sourceBankAccountId, $dossierId);

        if (!$sourceBankAccount) {
            return $this->json(
                ['error' => 'Compte bancaire source introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $destinationBankAccount = $this->bankAccountRepository->findOneByIdAndDossierId((int) $destinationBankAccountId, $dossierId);

        if (!$destinationBankAccount) {
            return $this->json(
                ['error' => 'Compte bancaire destinataire introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        try {
            $bankingTransaction = $this->bankingTransactionService->create($sourceBankAccount, $destinationBankAccount, $data);

            return $this->json(
                $this->formatBankingTransaction($bankingTransaction),
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
     * Updates an existing banking transaction.
     */
    #[Route('/{bankingTransactionId}', name: 'update', methods: ['PATCH'])]
    public function update(int $dossierId, int $bankingTransactionId, Request $request): JsonResponse
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

        $bankingTransaction = $this->bankingTransactionRepository->getOneByIdAndDossierId($bankingTransactionId, $dossierId);

        if (!$bankingTransaction) {
            return $this->json(
                ['error' => 'Mouvement bancaire introuvable.'],
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

        $sourceBankAccount = $bankingTransaction->getSourceBankAccount();
        $destinationBankAccount = $bankingTransaction->getDestinationBankAccount();

        if (array_key_exists('source_bank_account_id', $data)) {
            $sourceBankAccountId = $data['source_bank_account_id'];
            $hasValidSourceBankAccountId = filter_var($sourceBankAccountId, FILTER_VALIDATE_INT) !== false;

            if (!$hasValidSourceBankAccountId) {
                return $this->json(
                    ['error' => 'Le compte bancaire source est invalide.'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            $sourceBankAccount = $this->bankAccountRepository->findOneByIdAndDossierId((int) $sourceBankAccountId, $dossierId);

            if (!$sourceBankAccount) {
                return $this->json(
                    ['error' => 'Compte bancaire source introuvable.'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }
        }

        if (array_key_exists('destination_bank_account_id', $data)) {
            $destinationBankAccountId = $data['destination_bank_account_id'];
            $hasValidDestinationBankAccountId = filter_var($destinationBankAccountId, FILTER_VALIDATE_INT) !== false;

            if (!$hasValidDestinationBankAccountId) {
                return $this->json(
                    ['error' => 'Le compte bancaire destinataire est invalide.'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }

            $destinationBankAccount = $this->bankAccountRepository->findOneByIdAndDossierId((int) $destinationBankAccountId, $dossierId);

            if (!$destinationBankAccount) {
                return $this->json(
                    ['error' => 'Compte bancaire destinataire introuvable.'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }
        }

        try {
            $bankingTransaction = $this->bankingTransactionService->update($bankingTransaction, $sourceBankAccount, $destinationBankAccount, $data);

            return $this->json(
                $this->formatBankingTransaction($bankingTransaction),
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
     * Deletes an existing banking transaction.
     */
    #[Route('/{bankingTransactionId}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $dossierId, int $bankingTransactionId): JsonResponse
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

        $bankingTransaction = $this->bankingTransactionRepository->getOneByIdAndDossierId($bankingTransactionId, $dossierId);

        if (!$bankingTransaction) {
            return $this->json(
                ['error' => 'Mouvement bancaire introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $this->bankingTransactionService->delete($bankingTransaction);

        return $this->json(
            ['message' => 'Mouvement bancaire supprimé avec succès.'],
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Converts a submitted date into an immutable date.
     */
    private function createDateFromValue(mixed $dateValue): DateTimeImmutable
    {
        $hasValidString = is_string($dateValue) && trim($dateValue) !== '';

        if (!$hasValidString) {
            throw new \InvalidArgumentException(
                'La date est invalide.'
            );
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
        $isValidDate = $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $dateValue;

        if (!$isValidDate) {
            throw new \InvalidArgumentException(
                'La date doit respecter le format AAAA-MM-JJ.'
            );
        }

        return $date;
    }

    /**
     * Formats a banking transaction for the API response.
     */
    private function formatBankingTransaction(BankingTransaction $bankingTransaction): array
    {
        return [
            'id' => $bankingTransaction->getId(),
            'amount' => $bankingTransaction->getAmount(),
            'operation_date' => $bankingTransaction->getOperationDate()->format('Y-m-d'),
            'movement_type' => $bankingTransaction->getMovementType(),
            'source_bank_account' => $this->formatBankAccount($bankingTransaction->getSourceBankAccount()),
            'destination_bank_account' => $this->formatBankAccount($bankingTransaction->getDestinationBankAccount()),
            'created_at' => $bankingTransaction->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $bankingTransaction->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Formats bank account information for the API response.
     */
    private function formatBankAccount(BankAccount $bankAccount): array
    {
        return [
            'id' => $bankAccount->getId(),
            'bank_name' => $bankAccount->getBankName(),
            'account_type' => $bankAccount->getAccountType(),
            'account_label' => $bankAccount->getAccountLabel(),
            'account_number_masked' => $bankAccount->getAccountNumberMasked(),
        ];
    }
}