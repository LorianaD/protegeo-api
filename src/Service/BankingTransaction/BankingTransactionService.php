<?php

namespace App\Service\BankingTransaction;

use App\Entity\BankAccount;
use App\Entity\BankingTransaction;
use App\Enum\BankingMovementType;
use App\Repository\BankingTransactionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class BankingTransactionService
{
    public function __construct(
        private BankingTransactionRepository $bankingTransactionRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Returns all banking transactions associated with a dossier.
     *
     * @return BankingTransaction[]
     */
    public function getByDossierId(int $dossierId): array
    {
        return $this->bankingTransactionRepository->getByDossierId($dossierId);
    }

    /**
     * Returns banking transactions associated with a dossier during a specific period.
     *
     * @return BankingTransaction[]
     */
    public function getByDossierIdAndPeriod(int $dossierId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $this->validatePeriod($startDate, $endDate);

        return $this->bankingTransactionRepository->getByDossierIdAndPeriod($dossierId, $startDate, $endDate);
    }

    /**
     * Returns all banking transactions associated with a bank account.
     *
     * @return BankingTransaction[]
     */
    public function getByBankAccountId(int $bankAccountId): array
    {
        return $this->bankingTransactionRepository->getByBankAccountId($bankAccountId);
    }

    /**
     * Creates a banking transaction between two financial accounts.
     */
    public function create(BankAccount $sourceBankAccount, BankAccount $destinationBankAccount, array $data): BankingTransaction
    {
        $this->validateRequiredData($data);

        $operationDate = $this->validateDate($data['operation_date']);

        $this->validateBankAccounts($sourceBankAccount, $destinationBankAccount, $operationDate);

        $bankingTransaction = new BankingTransaction();

        $bankingTransaction->setSourceBankAccount($sourceBankAccount);
        $bankingTransaction->setDestinationBankAccount($destinationBankAccount);

        $this->setBankingTransactionData($bankingTransaction, $data);

        $this->em->persist($bankingTransaction);
        $this->em->flush();

        return $bankingTransaction;
    }

    /**
     * Updates an existing banking transaction.
     */
    public function update(BankingTransaction $bankingTransaction, BankAccount $sourceBankAccount, BankAccount $destinationBankAccount, array $data): BankingTransaction
    {
        $operationDate = $bankingTransaction->getOperationDate();

        if (array_key_exists('operation_date', $data)) {
            $operationDate = $this->validateDate($data['operation_date']);
        }

        $hasOperationDate = $operationDate instanceof DateTimeImmutable;

        if (!$hasOperationDate) {
            throw new \InvalidArgumentException(
                'La date de l’opération est obligatoire.'
            );
        }

        $this->validateBankAccounts($sourceBankAccount, $destinationBankAccount, $operationDate);

        $bankingTransaction->setSourceBankAccount($sourceBankAccount);
        $bankingTransaction->setDestinationBankAccount($destinationBankAccount);

        $this->setBankingTransactionData($bankingTransaction, $data);

        $bankingTransaction->setUpdatedAt(new DateTimeImmutable());

        $this->em->flush();

        return $bankingTransaction;
    }

    /**
     * Deletes an existing banking transaction.
     */
    public function delete(BankingTransaction $bankingTransaction): void
    {
        $this->em->remove($bankingTransaction);
        $this->em->flush();
    }

    /**
     * Sets banking transaction data from the submitted values.
     */
    private function setBankingTransactionData(BankingTransaction $bankingTransaction, array $data): void
    {
        if (array_key_exists('amount', $data)) {
            $bankingTransaction->setAmount(
                $this->validateAmount($data['amount'])
            );
        }

        if (array_key_exists('operation_date', $data)) {
            $bankingTransaction->setOperationDate(
                $this->validateDate($data['operation_date'])
            );
        }

        if (array_key_exists('movement_type', $data)) {
            $this->validateMovementType($data['movement_type']);

            $bankingTransaction->setMovementType(
                $data['movement_type']
            );
        }
    }

    /**
     * Checks whether all required banking transaction values are provided.
     */
    private function validateRequiredData(array $data): void
    {
        $hasAmount = array_key_exists('amount', $data) && $data['amount'] !== '' && $data['amount'] !== null;

        if (!$hasAmount) {
            throw new \InvalidArgumentException(
                'Le montant est obligatoire.'
            );
        }

        $hasOperationDate = !empty($data['operation_date']);

        if (!$hasOperationDate) {
            throw new \InvalidArgumentException(
                'La date de l’opération est obligatoire.'
            );
        }

        $hasMovementType = !empty($data['movement_type']);

        if (!$hasMovementType) {
            throw new \InvalidArgumentException(
                'Le type de mouvement est obligatoire.'
            );
        }
    }

    /**
     * Checks whether both accounts can be used for the banking transaction.
     */
    private function validateBankAccounts(BankAccount $sourceBankAccount, BankAccount $destinationBankAccount, DateTimeImmutable $operationDate): void
    {
        $sourceBankAccountId = $sourceBankAccount->getId();
        $destinationBankAccountId = $destinationBankAccount->getId();
        $accountsAreIdentical = $sourceBankAccountId === $destinationBankAccountId;

        if ($accountsAreIdentical) {
            throw new \InvalidArgumentException(
                'Le compte source et le compte destinataire doivent être différents.'
            );
        }

        $sourceDossierId = $sourceBankAccount->getDossier()?->getId();
        $destinationDossierId = $destinationBankAccount->getDossier()?->getId();
        $accountsBelongToSameDossier = $sourceDossierId !== null && $sourceDossierId === $destinationDossierId;

        if (!$accountsBelongToSameDossier) {
            throw new \InvalidArgumentException(
                'Les comptes bancaires doivent appartenir au même dossier.'
            );
        }

        $sourceAccountOpenedAt = $sourceBankAccount->getOpenedAt();
        $destinationAccountOpenedAt = $destinationBankAccount->getOpenedAt();

        $sourceAccountWasNotOpened = $sourceAccountOpenedAt instanceof DateTimeImmutable && $operationDate < $sourceAccountOpenedAt;
        $destinationAccountWasNotOpened = $destinationAccountOpenedAt instanceof DateTimeImmutable && $operationDate < $destinationAccountOpenedAt;

        if ($sourceAccountWasNotOpened || $destinationAccountWasNotOpened) {
            throw new \InvalidArgumentException(
                'La date de l’opération ne peut pas être antérieure à la date d’ouverture des comptes.'
            );
        }

        $sourceAccountClosedAt = $sourceBankAccount->getClosedAt();
        $destinationAccountClosedAt = $destinationBankAccount->getClosedAt();

        $sourceAccountWasClosed = $sourceAccountClosedAt instanceof DateTimeImmutable && $operationDate > $sourceAccountClosedAt;
        $destinationAccountWasClosed = $destinationAccountClosedAt instanceof DateTimeImmutable && $operationDate > $destinationAccountClosedAt;

        if ($sourceAccountWasClosed || $destinationAccountWasClosed) {
            throw new \InvalidArgumentException(
                'La date de l’opération ne peut pas être postérieure à la date de clôture des comptes.'
            );
        }
    }

    /**
     * Checks whether the banking movement type is valid.
     */
    private function validateMovementType(mixed $movementType): void
    {
        $isStringMovementType = is_string($movementType);
        $isValidMovementType = $isStringMovementType && BankingMovementType::isValid($movementType);

        if (!$isValidMovementType) {
            throw new \InvalidArgumentException(
                'Le type de mouvement est invalide.'
            );
        }
    }

    /**
     * Validates and formats the banking transaction amount.
     */
    private function validateAmount(mixed $amount): string
    {
        $normalizedAmount = is_string($amount) ? str_replace(',', '.', trim($amount)) : $amount;
        $isNumericAmount = is_numeric($normalizedAmount);

        if (!$isNumericAmount) {
            throw new \InvalidArgumentException(
                'Le montant doit être un nombre valide.'
            );
        }

        $isPositiveAmount = (float) $normalizedAmount > 0;

        if (!$isPositiveAmount) {
            throw new \InvalidArgumentException(
                'Le montant doit être supérieur à zéro.'
            );
        }

        return number_format((float) $normalizedAmount, 3, '.', '');
    }

    /**
     * Converts a date value into an immutable date.
     */
    private function validateDate(mixed $date): DateTimeImmutable
    {
        $hasValidString = is_string($date) && trim($date) !== '';

        if (!$hasValidString) {
            throw new \InvalidArgumentException(
                'La date de l’opération est invalide.'
            );
        }

        $operationDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $isValidDate = $operationDate instanceof DateTimeImmutable && $operationDate->format('Y-m-d') === $date;

        if (!$isValidDate) {
            throw new \InvalidArgumentException(
                'La date de l’opération doit respecter le format AAAA-MM-JJ.'
            );
        }

        return $operationDate;
    }

    /**
     * Checks whether the requested period is valid.
     */
    private function validatePeriod(DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        $isInvalidPeriod = $startDate > $endDate;

        if ($isInvalidPeriod) {
            throw new \InvalidArgumentException(
                'La date de début doit être antérieure ou égale à la date de fin.'
            );
        }
    }
}