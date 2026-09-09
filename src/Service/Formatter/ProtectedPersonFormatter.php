<?php

namespace App\Service\Formatter;

use App\Entity\ProtectedPerson;

class ProtectedPersonFormatter
{
    /**
     * Formats a protected person for the API response.
     */
    public function format(ProtectedPerson $protectedPerson): array
    {
        return [
            'id' => $protectedPerson->getId(),
            'photo_url' => $protectedPerson->getPhotoUrl(),
            'civility' => $protectedPerson->getCivility(),
            'firstname' => $protectedPerson->getFirstname(),
            'lastname' => $protectedPerson->getLastname(),
            'birth_date' => $protectedPerson->getBirthDate()?->format('Y-m-d'),
            'birth_place' => $protectedPerson->getBirthPlace(),
            'nationality' => $protectedPerson->getNationality(),
            'family_situation' => $protectedPerson->getFamilySituation(),
            'children_situation' => $protectedPerson->getChildrenSituation(),
            'address' => $protectedPerson->getAddress(),
            'postal_code' => $protectedPerson->getPostalCode(),
            'city' => $protectedPerson->getCity(),
            'phone_number' => $protectedPerson->getPhoneNumber(),
            'email' => $protectedPerson->getEmail(),
            'profession' => $protectedPerson->getProfession(),
            'autonomy_level' => $protectedPerson->getAutonomyLevel(),
            'situation_summary' => $protectedPerson->getSituationSummary(),
            'deceased_at' => $protectedPerson->getDeceasedAt()?->format('Y-m-d'),
            'family_note' => $protectedPerson->getFamilyNote(),
            'created_at' => $protectedPerson->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $protectedPerson->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}