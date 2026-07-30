<?php

namespace App\Service\BankAccount;

use App\Entity\BankAccount;
use App\Entity\Dossier;
use App\Enum\BankAccountType;
use App\Repository\BankAccountRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class BankAccountService
{
    public function __construct(
        private BankAccountRepository $bankAccountRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Returns all bank accounts for a dossier.
     *
     * @return BankAccount[]
     */
    public function getByDossierId(int $dossierId): array
    {

        return $this->bankAccountRepository->findByDossierId($dossierId);
    }

    /**
     * Returns one bank account for a dossier.
     */
    public function getByIdAndDossierId(int $bankAccountId, int $dossierId): ?BankAccount
    {
        return $this->bankAccountRepository->findOneByIdAndDossierId(
            $bankAccountId,
            $dossierId
        );
    }

    /**
     * Creates a bank account.
     */
    public function create(Dossier $dossier, array $data): BankAccount
    {
        $this->validateRequiredData($data);

        $bankAccount = new BankAccount();

        $bankAccount->setDossier($dossier);

        $this->setBankAccountData($bankAccount, $data);

        $this->em->persist($bankAccount);
        $this->em->flush();

        return $bankAccount;
    }

    /**
     * Updates a bank account.
     */
    public function update(BankAccount $bankAccount, array $data): BankAccount
    {
        $this->setBankAccountData($bankAccount, $data);

        $bankAccount->setUpdatedAt(new DateTimeImmutable());

        $this->em->flush();

        return $bankAccount;
    }

    /**
     * Sets bank account data.
     */
    private function setBankAccountData(BankAccount $bankAccount, array $data): void
    {
        if (array_key_exists('bank_name', $data)) {
            $bankAccount->setBankName($data['bank_name']);
        }

        if (array_key_exists('agency_name', $data)) {
            $bankAccount->setAgencyName($data['agency_name']);
        }

        if (array_key_exists('account_type', $data)) {
            $this->validateAccountType($data['account_type']);
            $bankAccount->setAccountType($data['account_type']);
        }

        if (array_key_exists('account_label', $data)) {
            $bankAccount->setAccountLabel($data['account_label']);
        }

        if (array_key_exists('account_number_masked', $data)) {
            $bankAccount->setAccountNumberMasked($data['account_number_masked']);
        }

        if (array_key_exists('iban_masked', $data)) {
            $bankAccount->setIbanMasked($data['iban_masked']);
        }

        if (array_key_exists('bic', $data)) {
            $bankAccount->setBic($data['bic']);
        }

        if (array_key_exists('opened_at', $data)) {
            $bankAccount->setOpenedAt(
                $this->validateDate($data['opened_at'], 'La date d’ouverture')
            );
        }

        if (array_key_exists('closed_at', $data)) {
            $closedAt = $data['closed_at']
                ? $this->validateDate($data['closed_at'], 'La date de clôture')
                : null;

            $bankAccount->setClosedAt($closedAt);
        }

        $this->validateBankAccountDates($bankAccount);
    }

    /**
     * Checks whether all required bank account values are provided.
     */
    private function validateRequiredData(array $data): void
    {
        $hasAccountType = !empty($data['account_type']);

        if (!$hasAccountType) {
            throw new \InvalidArgumentException(
                'Le type de compte bancaire est obligatoire.'
            );
        }
    }

    /**
     * Checks whether the bank account type is valid.
     */
    private function validateAccountType(mixed $accountType): void
    {
        $isStringAccountType = is_string($accountType);
        $isValidAccountType = $isStringAccountType && BankAccountType::isValid($accountType);

        if (!$isValidAccountType) {
            throw new \InvalidArgumentException(
                'Le type de compte bancaire est invalide.'
            );
        }
    }

    /**
     * Converts a submitted date into an immutable date.
     */
    private function validateDate(mixed $date, string $fieldName): DateTimeImmutable
    {
        $hasValidString = is_string($date) && trim($date) !== '';

        if (!$hasValidString) {
            throw new \InvalidArgumentException(
                $fieldName . ' est invalide.'
            );
        }

        $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $isValidDate = $dateValue instanceof DateTimeImmutable && $dateValue->format('Y-m-d') === $date;

        if (!$isValidDate) {
            throw new \InvalidArgumentException(
                $fieldName . ' doit respecter le format AAAA-MM-JJ.'
            );
        }

        return $dateValue;
    }

    /**
     * Checks whether the bank account dates are consistent.
     */
    private function validateBankAccountDates(BankAccount $bankAccount): void
    {
        $openedAt = $bankAccount->getOpenedAt();
        $closedAt = $bankAccount->getClosedAt();
        $hasInvalidPeriod = $openedAt instanceof DateTimeImmutable && $closedAt instanceof DateTimeImmutable && $closedAt < $openedAt;

        if ($hasInvalidPeriod) {
            throw new \InvalidArgumentException(
                'La date de clôture doit être postérieure ou égale à la date d’ouverture.'
            );
        }
    }
}