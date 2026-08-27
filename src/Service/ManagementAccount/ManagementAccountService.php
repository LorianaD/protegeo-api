<?php

namespace App\Service\ManagementAccount;

use App\Entity\Dossier;
use App\Entity\ManagementAccount;
use App\Enum\ManagementAccountStatus;
use App\Repository\ManagementAccountRepository;
use Doctrine\ORM\EntityManagerInterface;

class ManagementAccountService
{
    public function __construct(
        private ManagementAccountRepository $managementAccountRepository,
        private EntityManagerInterface $em,
    ) {}

    /**
     * Returns all management accounts for the given dossier.
     */
    public function getManagementAccountsByDossier(Dossier $dossier): array
    {
        return $this->managementAccountRepository->findByDossier($dossier);
    }

    /**
     * Returns the management account for the given dossier and year.
     */
    public function getManagementAccountByYear(Dossier $dossier, int $year): ?ManagementAccount
    {
        return $this->managementAccountRepository->findOneByDossierAndYear($dossier, $year);
    }

    /**
     * Creates a new management account.
     */
    public function createManagementAccount(ManagementAccount $managementAccount): ManagementAccount
    {
        $this->em->persist($managementAccount);
        $this->em->flush();

        return $managementAccount;
    }

    /**
     * Creates the initial management account for a new dossier.
     */
    public function createInitial(Dossier $dossier): ManagementAccount
    {
        $year = $dossier->getOpenedAt()->format('Y');

        $managementAccount = new ManagementAccount();

        $managementAccount
            ->setDossier($dossier)
            ->setYear(new \DateTime($year . '-01-01'))
            ->setStatus(ManagementAccountStatus::IN_PROGRESS);

        $this->em->persist($managementAccount);

        return $managementAccount;
    }

    /**
     * Saves changes to an existing management account.
     */
    public function updateManagementAccount(ManagementAccount $managementAccount): void
    {
        $managementAccount->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();
    }
}