<?php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\DossierUser;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierUser>
 */
class DossierUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierUser::class);
    }

    //    /**
    //     * @return DossierUser[] Returns an array of DossierUser objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DossierUser
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    /**
     * Récupère les utilisateurs rattachés à un dossier.
     *
     * @return DossierUser[]
     */
    public function findByDossier(Dossier $dossier) : array
    {
        return $this->createQueryBuilder('du')
            ->andWhere('du.dossier = :dossier')
            ->setParameter('dossier', $dossier)
            ->orderBy('du.roleType', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les dossiers auxquels un utilisateur est rattaché.
     *
     * @return DossierUser[]
     */
    public function findByUser(User $user) : array
    {
        return $this->createQueryBuilder('du')
            ->andWhere('du.user = :user')
            ->setParameter('user', $user)
            ->orderBy('du.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche la liaison entre un utilisateur et un dossier.
     */
    public function findOneByUserAndDossier( User $user, Dossier $dossier ) : ?DossierUser
    {
        return $this->createQueryBuilder('du')
            ->andWhere('du.user = :user')
            ->andWhere('du.dossier = :dossier')
            ->setParameter('user', $user)
            ->setParameter('dossier', $dossier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vérifie si un utilisateur est autorisé à accéder à un dossier.
     */
    public function userHasAccess(User $user, Dossier $dossier) : bool
    {
        $result = $this->createQueryBuilder('du')
            ->select('COUNT(du.id)')
            ->andWhere('du.user = :user')
            ->andWhere('du.dossier = :dossier')
            ->setParameter('user', $user)
            ->setParameter('dossier', $dossier)
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int) $result > 0;
    }

    /**
     * Récupère les dossiers ouverts liés à un utilisateur.
     *
     * @return DossierUser[]
     */
    public function findOpenDossiersByUser(User $user): array
    {
        return $this->createQueryBuilder('du')
            ->innerJoin('du.dossier', 'd')
            ->addSelect('d')
            ->andWhere('du.user = :user')
            ->andWhere('d.closedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('d.openedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
