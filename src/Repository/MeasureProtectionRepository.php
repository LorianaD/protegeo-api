<?php

namespace App\Repository;

use App\Entity\MeasureProtection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeasureProtection>
 */
class MeasureProtectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeasureProtection::class);
    }

    /**
     * @return MeasureProtection[]
     */
    public function findByDossierId(int $dossierId): array
    {
        return $this->createQueryBuilder('measureProtection')
            ->innerJoin('measureProtection.dossier', 'dossier')
            ->addSelect('dossier')
            ->andWhere('dossier.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
            ->orderBy('measureProtection.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MeasureProtection[]
     */
    public function findByDossierIdAndUser(int $dossierId, User $user) : array
    {
        return $this->createQueryBuilder('measureProtection')
            ->innerJoin('measureProtection.dossier', 'dossier')
            ->innerJoin('dossier.dossierUsers', 'dossierUser')
            ->addSelect('dossier')
            ->andWhere('dossier.id = :dossierId')
            ->andWhere('dossierUser.user = :user')
            ->setParameter('dossierId', $dossierId)
            ->setParameter('user', $user)
            ->orderBy('measureProtection.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentByDossierIdAndUser(int $dossierId, User $user) : ?MeasureProtection
    {
        $today = new \DateTimeImmutable();

        return $this->createQueryBuilder('m')
            ->innerJoin('m.dossier', 'd')
            ->innerJoin('d.dossierUsers', 'du')
            ->addSelect('d')
            ->andWhere('d.id = :dossierId')
            ->andWhere('du.user = :user')
            ->andWhere('m.startDate <= :today')
            ->andWhere('(m.endDate IS NULL OR m.endDate >= :today)')
            ->setParameter('dossierId', $dossierId)
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->orderBy('m.startDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdAndDossierIdAndUser(int $measureId, int $dossierId, User $user)
    {
        return $this->createQueryBuilder('measureProtection')
            ->innerJoin('measureProtection.dossier', 'dossier')
            ->innerJoin('dossier.dossierUsers', 'dossierUser')
            ->addSelect('dossier')
            ->andWhere('measureProtection.id = :measureId')
            ->andWhere('dossier.id = :dossierId')
            ->andWhere('dossierUser.user = :user')
            ->setParameter('measureId', $measureId)
            ->setParameter('dossierId', $dossierId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}