<?php

namespace App\Repository;

use App\Entity\BankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BankAccount>
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    /**
     * Returns all bank accounts linked to a dossier.
     *
     * @return BankAccount[]
     */
    public function findByDossierId(int $dossierId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.dossier = :dossierId')
            ->setParameter('dossierId', $dossierId)
            ->orderBy('b.bankName', 'ASC')
            ->addOrderBy('b.accountType', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns one bank account linked to a dossier.
     */
    public function findOneByIdAndDossierId(int $bankAccountId, int $dossierId): ?BankAccount
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.id = :bankAccountId')
            ->andWhere('b.dossier = :dossierId')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('dossierId', $dossierId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
