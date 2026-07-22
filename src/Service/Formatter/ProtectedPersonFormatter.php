<?php

namespace App\Service\Formatter;

use App\Entity\ProtectedPerson;

class ProtectedPersonFormatter
{
    public function format(ProtectedPerson $protectedPerson): array
    {
        return [
            'id' => $protectedPerson->getId(),
            'photoUrl' => $protectedPerson->getPhotoUrl(),
            'civility' => $protectedPerson->getCivility(),
            'firstname' => $protectedPerson->getFirstname(),
            'lastname' => $protectedPerson->getLastname(),
            'birthDate' => $protectedPerson->getBirthDate()?->format('Y-m-d'),
            'birthPlace' => $protectedPerson->getBirthPlace(),
            'nationality' => $protectedPerson->getNationality(),
            'familySituation' => $protectedPerson->getFamilySituation(),
            'childrenSituation' => $protectedPerson->getChildrenSituation(),
            'address' => $protectedPerson->getAddress(),
            'postalCode' => $protectedPerson->getPostalCode(),
            'city' => $protectedPerson->getCity(),
            'phoneNumber' => $protectedPerson->getPhoneNumber(),
            'email' => $protectedPerson->getEmail(),
            'profession' => $protectedPerson->getProfession(),
            'autonomyLevel' => $protectedPerson->getAutonomyLevel(),
            'situationSummary' => $protectedPerson->getSituationSummary(),
            'deceasedAt' => $protectedPerson->getDeceasedAt()?->format('Y-m-d'),
            'familyNote' => $protectedPerson->getFamilyNote(),
            'createdAt' => $protectedPerson->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $protectedPerson->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}