<?php

namespace App\Service\Formatter;

use App\Entity\Dossier;
use App\Entity\MeasureProtection;

class DossierFormatter
{
    public function __construct(
        private MeasureProtectionFormatter $measureProtectionFormatter
    ) {}

    public function format(Dossier $dossier): array
    {
        return [
            'id' => $dossier->getId(),
            'reference_number' => $dossier->getReferenceNumber(),
            'opened_at' => $dossier->getOpenedAt()?->format('Y-m-d'),
            'closed_at' => $dossier->getClosedAt()?->format('Y-m-d'),
        ];
    }

    public function formatWithRoleType(Dossier $dossier, string $roleType): array
    {
        $dossierData = $this->format($dossier);

        $dossierData['role_type'] = $roleType;

        return $dossierData;
    }

    public function formatWithProtectedPerson(Dossier $dossier): array
    {
        $dossierData = $this->format($dossier);

        $protectedPerson = $dossier->getProtectedPerson();

        if (!$protectedPerson) {
            $dossierData['protected_person'] = null;

            return $dossierData;
        }

        $dossierData['protected_person'] = [
            'id' => $protectedPerson->getId(),
            'civility' => $protectedPerson->getCivility(),
            'firstname' => $protectedPerson->getFirstname(),
            'lastname' => $protectedPerson->getLastname(),
        ];

        return $dossierData;
    }

    public function formatForUserList(Dossier $dossier, string $roleType): array
    {
        $dossierData = $this->formatWithProtectedPerson($dossier);

        $dossierData['role_type'] = $roleType;

        $latestMeasure = $this->getLatestMeasureProtection($dossier);

        $dossierData['measure'] = $latestMeasure
            ? $this->measureProtectionFormatter->format($latestMeasure)
            : null;

        return $dossierData;
    }

    /**
     * Returns the latest protection measure registered for the dossier.
     */
    private function getLatestMeasureProtection(Dossier $dossier): ?MeasureProtection
    {
        $measureProtections = $dossier
            ->getMeasureProtections()
            ->toArray();

        if ($measureProtections === []) {
            return null;
        }

        usort(
            $measureProtections,
            function (
                MeasureProtection $firstMeasure,
                MeasureProtection $secondMeasure
            ): int {
                return $secondMeasure->getStartDate()
                    <=> $firstMeasure->getStartDate();
            }
        );

        return $measureProtections[0];
    }
}