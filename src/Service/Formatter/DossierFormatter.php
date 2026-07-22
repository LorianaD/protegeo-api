<?php

namespace App\Service\Formatter;

use App\Entity\Dossier;

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
}