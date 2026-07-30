<?php

namespace App\Service\BankAccount;

use App\Entity\BankAccount;
use App\Entity\Dossier;
use App\Enum\BankAccountType;
use App\Repository\BankAccountRepository;
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
        if (empty($data['account_type'])) {
            throw new \InvalidArgumentException(
                'Le type de compte bancaire est obligatoire.'
            );
        }

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
                new \DateTimeImmutable($data['opened_at'])
            );
        }

        if (array_key_exists('closed_at', $data)) {
            $bankAccount->setClosedAt(
                $data['closed_at']
                    ? new \DateTimeImmutable($data['closed_at'])
                    : null
            );
        }
    }

    /**
     * Checks whether the account type is valid.
     */
    private function validateAccountType(string $accountType): void
    {
        if (!BankAccountType::isValid($accountType)) {
            throw new \InvalidArgumentException(
                'Le type de compte bancaire est invalide.'
            );
        }
    }
}