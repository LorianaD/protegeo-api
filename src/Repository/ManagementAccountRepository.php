<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\ManagementAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ManagementAccount>
 */
class ManagementAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManagementAccount::class);
    }

    /**
     * Returns all management accounts for the given dossier.
     */
    public function findByDossier(Dossier $dossier): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.dossier = :dossier')
            ->setParameter('dossier', $dossier)
            ->orderBy('m.year', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the management account for the given dossier and year.
     */
    public function findOneByDossierAndYear(Dossier $dossier, int $year): ?ManagementAccount
    {
        $startDate = new \DateTimeImmutable("$year-01-01");
        $endDate = $startDate->modify('+1 year');

        // Search for the management account matching the requested year.
        return $this->createQueryBuilder('m')
            ->andWhere('m.dossier = :dossier')
            ->andWhere('m.year >= :startDate')
            ->andWhere('m.year < :endDate')
            ->setParameter('dossier', $dossier)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneById(int $managementAccountId): ?ManagementAccount
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :managementAccountId')
            ->setParameter('managementAccountId', $managementAccountId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns one management account linked to a dossier.
     */
    public function findOneByIdAndDossierId(int $managementAccountId,int $dossierId): ?ManagementAccount
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.dossier', 'dossier')
            ->andWhere('m.id = :managementAccountId')
            ->andWhere('dossier.id = :dossierId')
            ->setParameter('managementAccountId', $managementAccountId)
            ->setParameter('dossierId', $dossierId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
