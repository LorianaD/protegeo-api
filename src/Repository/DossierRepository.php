<?php

namespace App\Repository;

use App\Entity\Dossier;
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

    //    /**
    //     * @return Dossier[] Returns an array of Dossier objects
    //     */

    public function findByReferenceNumber(string $referenceNumber) : ?Dossier
    {
        return $this->createQueryBuilder('d')
           ->andWhere('d.referenceNumber = :referenceNumber')
           ->setParameter('referenceNumber', $referenceNumber)
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
}
