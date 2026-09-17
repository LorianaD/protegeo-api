<?php

namespace App\Service\ManagementAccount;

use App\Entity\Dossier;
use App\Entity\ManagementAccount;
use App\Entity\MeasureProtection;
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
     * Validates the management account period and prevents overlaps.
     */
    public function validatePeriod(Dossier $dossier, \DateTimeInterface $startDate, \DateTimeInterface $endDate): void 
    {
        if ($endDate <= $startDate) {
            throw new \InvalidArgumentException(
                'La date de fin doit être postérieure à la date de début.'
            );
        }

        $overlappingManagementAccount = $this
            ->managementAccountRepository
            ->findOverlappingPeriod(
                $dossier,
                $startDate,
                $endDate,
            );

        if ($overlappingManagementAccount) {
            throw new \InvalidArgumentException(
                'Un compte de gestion existe déjà pour tout ou partie de cette période.'
            );
        }
    }

    /**
     * Creates the initial management account from the protection measure start date.
     */
    public function createInitial(Dossier $dossier, MeasureProtection $measureProtection): ManagementAccount
    {
        $startDate = $measureProtection->getStartDate();

        if (!$startDate) {
            throw new \InvalidArgumentException(
                'La date de début de la mesure est obligatoire.'
            );
        }

        $endDate = (clone $startDate)->modify('+1 year');
        $year = $startDate->format('Y');

        $this->validatePeriod(
            $dossier,
            $startDate,
            $endDate
        );

        $managementAccount = new ManagementAccount();

        $managementAccount
            ->setDossier($dossier)
            ->setYear(new \DateTime($year . '-01-01'))
            ->setStartDate(clone $startDate)
            ->setEndDate($endDate)
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

    /**
     * Applies the management account status and keeps the sent date consistent.
     */
    public function applyStatus(
        ManagementAccount $managementAccount,
        string $status,
        ?\DateTimeImmutable $sentAt = null
    ): void {
        if (!ManagementAccountStatus::isValid($status)) {
            throw new \InvalidArgumentException(
                'Le statut est invalide.'
            );
        }

        $managementAccount->setStatus($status);

        if ($status === ManagementAccountStatus::SENT) {
            $managementAccount->setSentAt(
                $sentAt ?? new \DateTimeImmutable()
            );

            return;
        }

        $managementAccount->setSentAt(null);
    }
}