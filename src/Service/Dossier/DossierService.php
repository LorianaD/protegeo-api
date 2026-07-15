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

            $existingDossier = $this->dossierRepository->findByReferenceNumber($newReferenceNumber);
            $id = $dossier->getId();

            if ($existingDossier && $existingDossier->getId() !== $id) {
                throw new \InvalidArgumentException(
                    'Un dossier avec ce numéro de référence existe déjà.'
                );
            }

            $dossier->setReferenceNumber($newReferenceNumber);
        }

        if ($newOpenedAt) {
            $dossier->setOpenedAt(
                new \DateTimeImmutable($newOpenedAt)
            );
        }

        if (array_key_exists('closedAt', $data)) {

            if ($newClosedAt === null) {
                $dossier->setClosedAt(null);
            } else {
                $dossier->setClosedAt(
                    new \DateTimeImmutable($newClosedAt)
                );
            }
        }

        $this->em->flush();

        return $dossier;
    }

}