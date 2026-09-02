<?php

namespace App\Repository;

use App\Entity\Contacts;
use App\Entity\ProtectedPerson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository responsible for retrieving contact entities.
 *
 * Custom queries are scoped to a protected person in order to ensure
 * contacts are always retrieved within the correct dossier context.
 *
 * @extends ServiceEntityRepository<Contacts>
 */
class ContactsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contacts::class);
    }

    /**
     * Returns all contacts belonging to a protected person.
     *
     * Results can optionally be filtered by contact category
     * (family, professional or organization).
     *
     * Contacts are ordered alphabetically by last name and then by
     * organization name to provide a consistent display in the dashboard.
     *
     * @return Contacts[]
     */
    public function findByProtectedPerson(
        ProtectedPerson $protectedPerson,
        ?string $contactCategory = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.protectedPerson = :protectedPerson')
            ->setParameter('protectedPerson', $protectedPerson)
            ->orderBy('c.lastname', 'ASC')
            ->addOrderBy('c.organizationName', 'ASC');

        if ($contactCategory !== null) {
            $query
                ->andWhere('c.contactCategory = :contactCategory')
                ->setParameter('contactCategory', $contactCategory);
        }

        return $query
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a single contact belonging to the given protected person.
     *
     * Searching by both the contact identifier and the protected person
     * prevents retrieving a contact associated with another dossier.
     */
    public function findOneByIdAndProtectedPerson(int $contactId, ProtectedPerson $protectedPerson): ?Contacts
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :contactId')
            ->andWhere('c.protectedPerson = :protectedPerson')
            ->setParameter('contactId', $contactId)
            ->setParameter('protectedPerson', $protectedPerson)
            ->getQuery()
            ->getOneOrNullResult();
    }
}