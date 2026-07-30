<?php

namespace App\Service\Transaction;

use App\Entity\BankAccount;
use App\Entity\ManagementAccount;
use App\Entity\Transaction;
use App\Enum\PaymentMethod;
use App\Enum\TransactionCategoryGroup;
use App\Enum\TransactionCategoryType;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransactionService
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Returns all transactions linked to a management account.
     *
     * @return Transaction[]
     */
    public function getByManagementAccount(ManagementAccount $managementAccount): array
    {
        return $this->transactionRepository
            ->findByManagementAccount($managementAccount);
    }

    /**
     * Returns transactions linked to a management account for a specific month.
     *
     * @return Transaction[]
     */
    public function getByManagementAccountAndMonth(ManagementAccount $managementAccount, int $year, int $month): array
    {
        $this->validateMonth($month);

        return $this->transactionRepository
            ->findByManagementAccountAndMonth(
                $managementAccount,
                $year,
                $month
            );
    }

    /**
     * Returns transactions linked to a management account and transaction type.
     *
     * @return Transaction[]
     */
    public function getByManagementAccountAndType(ManagementAccount $managementAccount, string $transactionType): array
    {
        $this->validateTransactionType($transactionType);

        return $this->transactionRepository
            ->findByManagementAccountAndType(
                $managementAccount,
                $transactionType
            );
    }

    /**
     * Returns transactions linked to a management account and category group.
     *
     * @return Transaction[]
     */
    public function getByManagementAccountAndCategoryGroup(ManagementAccount $managementAccount, string $categoryGroup): array
    {
        $this->validateCategoryGroup($categoryGroup);

        return $this->transactionRepository
            ->findByManagementAccountAndCategoryGroup(
                $managementAccount,
                $categoryGroup
            );
    }

    /**
     * Returns transactions linked to a management account and category type.
     *
     * @return Transaction[]
     */
    public function getByManagementAccountAndCategoryType(ManagementAccount $managementAccount, string $categoryType): array
    {
        $this->validateCategoryType($categoryType);

        return $this->transactionRepository
            ->findByManagementAccountAndCategoryType(
                $managementAccount,
                $categoryType
            );
    }

    /**
     * Returns transactions linked to a bank account.
     *
     * @return Transaction[]
     */
    public function getByBankAccount(BankAccount $bankAccount): array
    {
        return $this->transactionRepository
            ->findByBankAccount($bankAccount);
    }

    /**
     * Creates a new transaction.
     */
    public function create(ManagementAccount $managementAccount, ?BankAccount $bankAccount, array $data): Transaction
    {
        $this->validateRequiredData($data);

        $transaction = new Transaction();

        $transaction->setAccount($managementAccount);
        $transaction->setBankAccount($bankAccount);

        $this->setTransactionData($transaction, $data);

        $this->em->persist($transaction);
        $this->em->flush();

        return $transaction;
    }

    /**
     * Updates an existing transaction.
     */
    public function update(Transaction $transaction, array $data, ?BankAccount $bankAccount = null): Transaction
    {
        if (array_key_exists('bank_account_id', $data)) {
            $transaction->setBankAccount($bankAccount);
        }

        $this->setTransactionData($transaction, $data);

        $transaction->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $transaction;
    }

    /**
     * Set transaction data from the given request values.
     */
    private function setTransactionData(Transaction $transaction, array $data): void
    {
        if (array_key_exists('transaction_type', $data)) {
            $this->validateTransactionType($data['transaction_type']);

            $transaction->setTransactionType(
                $data['transaction_type']
            );
        }

        if (array_key_exists('category_group', $data)) {
            $this->validateCategoryGroup($data['category_group']);

            $transaction->setCategoryGroup(
                $data['category_group']
            );
        }

        if (array_key_exists('category_type', $data)) {
            $this->validateCategoryType($data['category_type']);

            $transaction->setCategoryType(
                $data['category_type']
            );
        }

        if (array_key_exists('label', $data)) {
            $transaction->setLabel(
                $this->nullableString($data['label'])
            );
        }

        if (array_key_exists('amount', $data)) {
            $transaction->setAmount(
                $this->validateAmount($data['amount'])
            );
        }

        if (array_key_exists('operation_date', $data)) {
            $transaction->setOperationDate(
                $this->validateDate($data['operation_date'])
            );
        }

        if (array_key_exists('payment_method', $data)) {
            $paymentMethod = $this->nullableString(
                $data['payment_method']
            );

            if ($paymentMethod !== null) {
                $this->validatePaymentMethod($paymentMethod);
            }

            $transaction->setPaymentMethod($paymentMethod);
        }
    }

    /**
     * Checks whether all required transaction values are provided.
     */
    private function validateRequiredData(array $data): void
    {
        if (empty($data['transaction_type'])) {
            throw new \InvalidArgumentException(
                'Le type de transaction est obligatoire.'
            );
        }

        if (empty($data['category_group'])) {
            throw new \InvalidArgumentException(
                'Le groupe de catégorie est obligatoire.'
            );
        }

        if (empty($data['category_type'])) {
            throw new \InvalidArgumentException(
                'La catégorie est obligatoire.'
            );
        }

        if (
            !array_key_exists('amount', $data)
            || $data['amount'] === ''
            || $data['amount'] === null
        ) {
            throw new \InvalidArgumentException(
                'Le montant est obligatoire.'
            );
        }

        if (empty($data['operation_date'])) {
            throw new \InvalidArgumentException(
                'La date de l’opération est obligatoire.'
            );
        }
    }

    /**
     * Checks whether the transaction type is valid.
     */
    private function validateTransactionType(string $transactionType): void
    {
        if (!TransactionType::isValid($transactionType)) {
            throw new \InvalidArgumentException(
                'Le type de transaction est invalide.'
            );
        }
    }

    /**
     * Checks whether the category group is valid.
     */
    private function validateCategoryGroup(string $categoryGroup): void
    {
        if (!TransactionCategoryGroup::isValid($categoryGroup)) {
            throw new \InvalidArgumentException(
                'Le groupe de catégorie est invalide.'
            );
        }
    }

    /**
     * Checks whether the category type is valid.
     */
    private function validateCategoryType(string $categoryType): void
    {
        if (!TransactionCategoryType::isValid($categoryType)) {
            throw new \InvalidArgumentException(
                'La catégorie est invalide.'
            );
        }
    }

    /**
     * Checks whether the payment method is valid.
     */
    private function validatePaymentMethod(string $paymentMethod): void
    {
        if (!PaymentMethod::isValid($paymentMethod)) {
            throw new \InvalidArgumentException(
                'Le moyen de paiement est invalide.'
            );
        }
    }

    /**
     * Validates and formats the transaction amount.
     */
    private function validateAmount(mixed $amount): string
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException(
                'Le montant doit être un nombre valide.'
            );
        }

        if ((float) $amount <= 0) {
            throw new \InvalidArgumentException(
                'Le montant doit être supérieur à zéro.'
            );
        }

        return number_format((float) $amount, 3, '.', '');
    }

    /**
     * Converts a date value into an immutable date.
     */
    private function validateDate(mixed $date): \DateTimeImmutable
    {
        if (!is_string($date) || trim($date) === '') {
            throw new \InvalidArgumentException(
                'La date de l’opération est invalide.'
            );
        }

        try {
            return new \DateTimeImmutable($date);
        } catch (\Exception) {
            throw new \InvalidArgumentException(
                'La date de l’opération est invalide.'
            );
        }
    }

    /**
     * Checks whether the selected month is valid.
     */
    private function validateMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException(
                'Le mois doit être compris entre 1 et 12.'
            );
        }
    }

    /**
     * Returns a trimmed string or null when the value is empty.
     */
    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                'La valeur doit être une chaîne de caractères.'
            );
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}