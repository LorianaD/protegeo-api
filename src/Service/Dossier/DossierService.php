<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Repository\DossierRepository;
use Doctrine\ORM\EntityManagerInterface;

class DossierService
{
    public function __construct(private EntityManagerInterface $em, private DossierRepository $dossierRepository )
    {}

    public function createDossier(array $data): Dossier
    {
        $referenceNumber = $data['referenceNumber'] ?? null;

        if (!$referenceNumber) {
            throw new \InvalidArgumentException(
                'Le numéro de référence est obligatoire.'
            );
        }

        $existingDossier = $this->dossierRepository
            ->findByReferenceNumber($referenceNumber);

        if ($existingDossier) {
            throw new \InvalidArgumentException(
                'Un dossier avec ce numéro de référence existe déjà.'
            );
        }

        $newOpenedAt = new \DateTimeImmutable();

        $openedAt = $data['openedAt'] ?? null;

        if ($openedAt) {
            $newOpenedAt = new \DateTimeImmutable($openedAt);
        }

        $dossier = new Dossier();

        $dossier
            ->setReferenceNumber($referenceNumber)
            ->setOpenedAt($newOpenedAt);

        $this->em->persist($dossier);
        $this->em->flush();

        return $dossier;
    }

    function showDossier(int $id) : ?Dossier
    {
        return $this->dossierRepository->findOneById($id);
    }

    public function updateDossier(Dossier $dossier, array $data): Dossier
    {
        $newReferenceNumber = $data['referenceNumber'] ?? null;
        $newOpenedAt = $data['openedAt'] ?? null;
        $newClosedAt = $data['closedAt'] ?? null;

        if ($newReferenceNumber) {
            $existingDossier = $this->dossierRepository
                ->findByReferenceNumber($newReferenceNumber);

            $id = $dossier->getId();

            if ($existingDossier && $existingDossier->getId() !== $id) {
                throw new \InvalidArgumentException(
                    'Un dossier avec ce numéro de référence existe déjà.'
                );
            }

            $dossier->setReferenceNumber($newReferenceNumber);
        }

        $openedAt = $dossier->getOpenedAt();

        if ($newOpenedAt) {
            $openedAt = new \DateTimeImmutable($newOpenedAt);
        }

        $closedAt = $dossier->getClosedAt();

        if (array_key_exists('closedAt', $data)) {
            if ($newClosedAt === null) {
                $closedAt = null;
            } else {
                $closedAt = new \DateTimeImmutable($newClosedAt);
            }
        }

        if ($closedAt !== null && $closedAt < $openedAt) {
            throw new \InvalidArgumentException(
                'La date de clôture ne peut pas être antérieure à la date d’ouverture.'
            );
        }

        $dossier->setOpenedAt($openedAt);
        $dossier->setClosedAt($closedAt);

        $this->em->flush();

        return $dossier;
    }

}