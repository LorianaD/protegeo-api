<?php

namespace App\Service\Formatter;

use App\Entity\Dossier;
use App\Entity\ProtectedPerson;

class DossierFormatter
{
    public function format(Dossier $dossier) : array
    {
        return [
            'id' => $dossier->getId(),
            'referenceNumber' => $dossier->getReferenceNumber(),
            'openedAt' => $dossier->getOpenedAt()?->format('Y-m-d'),
            'closedAt' => $dossier->getClosedAt()?->format('Y-m-d'),
        ];
    }

    public function formatWithRoleType(Dossier $dossier, string $roleType) : array
    {
        $dossierData = $this->format($dossier);

        $dossierData['roleType'] = $roleType;

        return $dossierData;
    }

    public function formatWithProtectedPerson(Dossier $dossier): array
    {
        $dossierData = $this->format($dossier);

        $protectedPerson = $dossier->getProtectedPerson();

        if (!$protectedPerson) {
            $dossierData['protectedPerson'] = null;

            return $dossierData;
        }

        $dossierData['protectedPerson'] = [
            'id' => $protectedPerson->getId(),
            'civility' => $protectedPerson->getCivility(),
            'firstname' => $protectedPerson->getFirstname(),
            'lastname' => $protectedPerson->getLastname(),
        ];

        return $dossierData;
    }

    public function formatForUserList(Dossier $dossier, string $roleType) : array
    {
        $dossierData = $this->formatWithProtectedPerson($dossier);

        $dossierData['roleType'] = $roleType;

        return $dossierData;
    }
}