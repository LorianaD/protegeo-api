<?php

namespace App\Repository;

use App\Entity\BankingTransaction;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BankingTransaction>
 */
class BankingTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankingTransaction::class);
    }

    /**
     * Returns all banking transactions associated with a dossier.
     *
     * Both the source and destination accounts must belong to the requested dossier.
     *
     * @return BankingTransaction[]
     */
    public function getByDossierId(int $dossierId): array
    {
        return $this->createQueryBuilder('b')
            ->addSelect('sourceBankAccount', 'destinationBankAccount')
            ->innerJoin('b.sourceBankAccount', 'sourceBankAccount')
            ->innerJoin('b.destinationBankAccount', 'destinationBankAccount')
            ->innerJoin('sourceBankAccount.dossier', 'sourceDossier')
            ->innerJoin('destinationBankAccount.dossier', 'destinationDossier')
            ->andWhere('sourceDossier.id = :dossierId')
            ->andWhere('destinationDossier.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
            ->orderBy('b.operationDate', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the banking transactions associated with a dossier during a specific period.
     *
     * @return BankingTransaction[]
     */
    public function getByDossierIdAndPeriod(int $dossierId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('b')
            ->addSelect('sourceBankAccount', 'destinationBankAccount')
            ->innerJoin('b.sourceBankAccount', 'sourceBankAccount')
            ->innerJoin('b.destinationBankAccount', 'destinationBankAccount')
            ->innerJoin('sourceBankAccount.dossier', 'sourceDossier')
            ->innerJoin('destinationBankAccount.dossier', 'destinationDossier')
            ->andWhere('sourceDossier.id = :dossierId')
            ->andWhere('destinationDossier.id = :dossierId')
            ->andWhere('b.operationDate >= :startDate')
            ->andWhere('b.operationDate <= :endDate')
            ->setParameter('dossierId', $dossierId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('b.operationDate', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all banking transactions where the account is either the source or the destination.
     *
     * @return BankingTransaction[]
     */
    public function getByBankAccountId(int $bankAccountId): array
    {
        return $this->createQueryBuilder('b')
            ->addSelect('sourceBankAccount', 'destinationBankAccount')
            ->innerJoin('b.sourceBankAccount', 'sourceBankAccount')
            ->innerJoin('b.destinationBankAccount', 'destinationBankAccount')
            ->andWhere('(sourceBankAccount.id = :bankAccountId OR destinationBankAccount.id = :bankAccountId)')
            ->setParameter('bankAccountId', $bankAccountId)
            ->orderBy('b.operationDate', 'DESC')
            ->addOrderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a banking transaction only when both related accounts belong to the requested dossier.
     */
    public function getOneByIdAndDossierId(int $bankingTransactionId, int $dossierId): ?BankingTransaction
    {
        return $this->createQueryBuilder('b')
            ->addSelect('sourceBankAccount', 'destinationBankAccount')
            ->innerJoin('b.sourceBankAccount', 'sourceBankAccount')
            ->innerJoin('b.destinationBankAccount', 'destinationBankAccount')
            ->innerJoin('sourceBankAccount.dossier', 'sourceDossier')
            ->innerJoin('destinationBankAccount.dossier', 'destinationDossier')
            ->andWhere('b.id = :bankingTransactionId')
            ->andWhere('sourceDossier.id = :dossierId')
            ->andWhere('destinationDossier.id = :dossierId')
            ->setParameter('bankingTransactionId', $bankingTransactionId)
            ->setParameter('dossierId', $dossierId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns a banking transaction associated with the specified bank account.
     */
    public function getOneByIdAndBankAccountId(int $bankingTransactionId, int $bankAccountId): ?BankingTransaction
    {
        return $this->createQueryBuilder('b')
            ->addSelect('sourceBankAccount', 'destinationBankAccount')
            ->innerJoin('b.sourceBankAccount', 'sourceBankAccount')
            ->innerJoin('b.destinationBankAccount', 'destinationBankAccount')
            ->andWhere('b.id = :bankingTransactionId')
            ->andWhere('(sourceBankAccount.id = :bankAccountId OR destinationBankAccount.id = :bankAccountId)')
            ->setParameter('bankingTransactionId', $bankingTransactionId)
            ->setParameter('bankAccountId', $bankAccountId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
