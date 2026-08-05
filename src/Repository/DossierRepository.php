<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dossier>
 */
class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    /**
     * Returns a dossier by reference when the user has access to it.
     */
    public function findOneByReferenceNumberAndUser(string $referenceNumber, User $user): ?Dossier 
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.dossierUsers', 'dossierUser')
            ->andWhere('d.referenceNumber = :referenceNumber')
            ->andWhere('dossierUser.user = :user')
            ->setParameter('referenceNumber', $referenceNumber)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
    * @return Dossier[] Returns an array of Dossier objects
    */    
    public function findOpenDossiers() : array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.closedAt IS NULL')
            ->orderBy('d.openedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
    * @return Dossier[]
    */
    public function findClosedDossiers() : array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.closedAt IS NOT NULL')
            ->orderBy('d.closedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(int $id) : ?Dossier
    {
        return $this->createQueryBuilder('d')
            ->select('d')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdAndUser(int $id, User $user) : ?Dossier
    {
        return $this->createQueryBuilder('dossier')
            ->innerJoin('dossier.dossierUsers', 'dossierUser')
            ->andWhere('dossier.id = :id')
            ->andWhere('dossierUser.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
