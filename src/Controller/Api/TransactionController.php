<?php

namespace App\Controller\Api;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\BankAccountRepository;
use App\Repository\DossierRepository;
use App\Repository\ManagementAccountRepository;
use App\Repository\TransactionRepository;
use App\Service\Dossier\DossierUserService;
use App\Service\ManagementAccount\ManagementAccountService;
use App\Service\Transaction\TransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{dossierId}/management-accounts/{managementAccountId}/transactions', name: 'api_transactions_')]
class TransactionController extends AbstractController
{
    public function __construct(
        private TransactionService $transactionService,
        private TransactionRepository $transactionRepository,
        private DossierRepository $dossierRepository,
        private ManagementAccountRepository $managementAccountRepository,
        private BankAccountRepository $bankAccountRepository,
    ) {}

    /**
     * Returns all transactions for a management account.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $dossierId, int $managementAccountId, Request $request): JsonResponse
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

        $managementAccount = $this->managementAccountRepository->findOneByIdAndDossierId(
            $managementAccountId,
            $dossierId
        );

        if (!$managementAccount) {
            return $this->json(
                ['error' => 'Compte de gestion introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        try {
            $year = $request->query->get('year');
            $month = $request->query->get('month');
            $transactionType = $request->query->get('transaction_type');
            $categoryGroup = $request->query->get('category_group');
            $categoryType = $request->query->get('category_type');
            $bankAccountId = $request->query->get('bank_account_id');

            if ($month !== null) {
                if ($year === null) {
                    return $this->json(
                        ['error' => 'L’année est obligatoire pour filtrer par mois.'],
                        JsonResponse::HTTP_BAD_REQUEST
                    );
                }

                $transactions = $this->transactionService->getByManagementAccountAndMonth(
                    $managementAccount,
                    (int) $year,
                    (int) $month
                );
            } elseif ($transactionType !== null) {
                $transactions = $this->transactionService ->getByManagementAccountAndType(
                    $managementAccount,
                    $transactionType
                );
            } elseif ($categoryGroup !== null) {
                $transactions = $this->transactionService->getByManagementAccountAndCategoryGroup(
                    $managementAccount,
                    $categoryGroup
                );
            } elseif ($categoryType !== null) {
                $transactions = $this->transactionService->getByManagementAccountAndCategoryType(
                    $managementAccount,
                    $categoryType
                );
            } elseif ($bankAccountId !== null) {
                $bankAccount = $this->bankAccountRepository->findOneByIdAndDossierId(
                    (int) $bankAccountId,
                    $dossierId
                );

                if (!$bankAccount) {
                    return $this->json(
                        ['error' => 'Compte bancaire introuvable.'],
                        JsonResponse::HTTP_NOT_FOUND
                    );
                }

                $transactions = $this->transactionService->getByBankAccount(
                    $bankAccount
                );

                $transactions = array_filter(
                    $transactions,
                    static fn (Transaction $transaction): bool => $transaction->getAccount()->getId() === $managementAccountId
                );
            } else {
                $transactions = $this->transactionService->getByManagementAccount(
                    $managementAccount
                );
            }

            return $this->json(
                array_values(
                    array_map(
                        fn (Transaction $transaction): array => $this->formatTransaction($transaction),
                        $transactions
                    )
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
     * Returns one transaction.
     */
    #[Route('/{transactionId}', name: 'show', methods: ['GET'])]
    public function show(int $dossierId, int $managementAccountId, int $transactionId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['error' => 'Utilisateur non authentifié.'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $dossier = $this->dossierRepository->findOneByIdAndUser(
            $dossierId, 
            $user
        );

        if (!$dossier) {
            return $this->json(
                ['error' => 'Dossier introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $managementAccount = $this->managementAccountRepository->findOneByIdAndDossierId(
            $managementAccountId,
            $dossierId
        );

        if (!$managementAccount) {
            return $this->json(
                ['error' => 'Compte de gestion introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $transaction = $this->transactionRepository->findOneByIdAndManagementAccountId(
            $transactionId, 
            $managementAccountId
        );

        if (!$transaction) {
            return $this->json(
                ['error' => 'Transaction introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return $this->json(
            $this->formatTransaction($transaction),
            JsonResponse::HTTP_OK
        );
    }

    /**
     * Creates a transaction.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $dossierId, int $managementAccountId, Request $request): JsonResponse
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
                ['error' => 'Dossier introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $managementAccount = $this->managementAccountRepository->findOneByIdAndDossierId(
            $managementAccountId,
            $dossierId
        );

        if (!$managementAccount) {
            return $this->json(
                ['error' => 'Compte de gestion introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Les données envoyées sont invalides.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $bankAccount = null;

        if (array_key_exists('bank_account_id', $data) && $data['bank_account_id'] !== null) {
            $bankAccount = $this->bankAccountRepository->findOneByIdAndDossierId(
                (int) $data['bank_account_id'],
                $dossierId
            );

            if (!$bankAccount) {
                return $this->json(
                    ['error' => 'Compte bancaire introuvable.'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }
        }

        try {
            $transaction = $this->transactionService->create(
                $managementAccount,
                $bankAccount,
                $data
            );

            return $this->json(
                $this->formatTransaction($transaction),
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
     * Updates a transaction.
     */
    #[Route('/{transactionId}', name: 'update', methods: ['PATCH'])]
    public function update(int $dossierId, int $managementAccountId, int $transactionId, Request $request): JsonResponse
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
                ['error' => 'Dossier introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $managementAccount = $this->managementAccountRepository->findOneByIdAndDossierId(
            $managementAccountId,
            $dossierId
        );

        if (!$managementAccount) {
            return $this->json(
                ['error' => 'Compte de gestion introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $transaction = $this->transactionRepository->findOneByIdAndManagementAccountId(
            $transactionId,
            $managementAccountId   
        );

        if (!$transaction) {
            return $this->json(
                ['error' => 'Transaction introuvable.'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $data = json_decode(
            $request->getContent(),
            true
        );

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Les données envoyées sont invalides.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $bankAccount = null;

        if (array_key_exists('bank_account_id', $data) && $data['bank_account_id'] !== null) {
            $bankAccount = $this->bankAccountRepository
                ->findOneByIdAndDossierId(
                    (int) $data['bank_account_id'],
                    $dossierId
                );

            if (!$bankAccount) {
                return $this->json(
                    ['error' => 'Compte bancaire introuvable.'],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }
        }

        try {
            $transaction = $this->transactionService->update(
                $transaction,
                $data,
                $bankAccount
            );

            return $this->json(
                $this->formatTransaction($transaction),
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
     * Formats a transaction for the API response.
     */
    private function formatTransaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->getId(),
            'transaction_type' => $transaction->getTransactionType(),
            'category_type' => $transaction->getCategoryType(),
            'category_group' => $transaction->getCategoryGroup(),
            'label' => $transaction->getLabel(),
            'amount' => $transaction->getAmount(),
            'operation_date' => $transaction->getOperationDate()->format('Y-m-d'),
            'payment_method' => $transaction->getPaymentMethod(),
            'bank_account_id' => $transaction->getBankAccount()?->getId(),
            'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $transaction->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}