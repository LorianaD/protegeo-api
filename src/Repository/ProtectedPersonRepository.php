<?php

namespace App\Repository;

use App\Entity\ProtectedPerson;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProtectedPerson>
 */
class ProtectedPersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProtectedPerson::class);
    }

    //    /**
    //     * @return ProtectedPerson[] Returns an array of ProtectedPerson objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ProtectedPerson
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findOneByDossierReference(string $referenceNumber)
    {
        return $this->createQueryBuilder('protectedPerson')
            ->innerJoin('protectedPerson.dossier', 'dossier')
            ->addSelect('dossier')
            ->andWhere('dossier.referenceNumber = :referenceNumber')
            ->setParameter('referenceNumber', $referenceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByDossierIdAndUser(int $dossierId, User $user)
    {
        return $this->createQueryBuilder('protectedPerson')
            ->innerJoin('protectedPerson.dossier', 'dossier')
            ->innerJoin('dossier.dossierUsers', 'dossierUser')
            ->addSelect('dossier')
            ->andWhere('dossier.id = :dossierId')
            ->andWhere('dossierUser.user = :user')
            ->setParameter('dossierId', $dossierId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
