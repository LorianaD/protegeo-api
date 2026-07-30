<?php

namespace App\Repository;

use App\Entity\BankAccount;
use App\Entity\ManagementAccount;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

     /**
     * Returns all transactions linked to a management account.
     *
     * @return Transaction[]
     */
    public function findByManagementAccount(ManagementAccount $managementAccount): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.account = :managementAccount')
            ->setParameter('managementAccount', $managementAccount)
            ->orderBy('t.operationDate', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns transactions linked to a management account for a specific month.
     *
     * @return Transaction[]
     */
    public function findByManagementAccountAndMonth(ManagementAccount $managementAccount, int $year, int $month): array
    {
        $startDate = new \DateTimeImmutable(
            sprintf('%04d-%02d-01', $year, $month)
        );

        $endDate = $startDate->modify('first day of next month');

        return $this->createQueryBuilder('t')
            ->andWhere('t.account = :managementAccount')
            ->andWhere('t.operationDate >= :startDate')
            ->andWhere('t.operationDate < :endDate')
            ->setParameter('managementAccount', $managementAccount)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.operationDate', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns transactions linked to a management account and transaction type.
     *
     * @return Transaction[]
     */
    public function findByManagementAccountAndType(ManagementAccount $managementAccount, string $transactionType): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.account = :managementAccount')
            ->andWhere('t.transactionType = :transactionType')
            ->setParameter('managementAccount', $managementAccount)
            ->setParameter('transactionType', $transactionType)
            ->orderBy('t.operationDate', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns transactions linked to a bank account.
     *
     * @return Transaction[]
     */
    public function findByBankAccount(BankAccount $bankAccount): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.bankAccount = :bankAccount')
            ->setParameter('bankAccount', $bankAccount)
            ->orderBy('t.operationDate', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns transactions linked to a management account and category group.
     *
     * @return Transaction[]
     */
    public function findByManagementAccountAndCategoryGroup(ManagementAccount $managementAccount, string $categoryGroup): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.account = :managementAccount')
            ->andWhere('t.categoryGroup = :categoryGroup')
            ->setParameter('managementAccount', $managementAccount)
            ->setParameter('categoryGroup', $categoryGroup)
            ->orderBy('t.operationDate', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns transactions linked to a management account and category type.
     *
     * @return Transaction[]
     */
    public function findByManagementAccountAndCategoryType(ManagementAccount $managementAccount, string $categoryType): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.account = :managementAccount')
            ->andWhere('t.categoryType = :categoryType')
            ->setParameter('managementAccount', $managementAccount)
            ->setParameter('categoryType', $categoryType)
            ->orderBy('t.operationDate', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(int $transactionId): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :transactionId')
            ->setParameter('transactionId', $transactionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns one transaction linked to a management account.
     */
    public function findOneByIdAndManagementAccountId(int $transactionId, int $managementAccountId): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.account', 'managementAccount')
            ->andWhere('t.id = :transactionId')
            ->andWhere('managementAccount.id = :managementAccountId')
            ->setParameter('transactionId', $transactionId)
            ->setParameter('managementAccountId', $managementAccountId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
