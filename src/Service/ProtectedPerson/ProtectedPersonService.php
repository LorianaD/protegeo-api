<?php

namespace App\Service\ProtectedPerson;

use App\Entity\Dossier;
use App\Entity\ProtectedPerson;
use App\Entity\User;
use App\Repository\ProtectedPersonRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProtectedPersonService
{
    public function __construct(
        private ProtectedPersonRepository $protectedPersonRepository,
        private EntityManagerInterface $em
    )
    {}

    public function getByDossierId(int $dossierId, User $user) : ProtectedPerson
    {
        $protectedPerson = $this->protectedPersonRepository->findOneByDossierIdAndUser($dossierId, $user);

        if (!$protectedPerson) {
            throw new \RuntimeException(
                'La personne protégée est introuvable ou vous n’avez pas accès à ce dossier.'
            );
        }

        return $protectedPerson;
    }

    public function create(Dossier $dossier, array $data) : ProtectedPerson
    {
        $this->validateRequiredData($data);

        $protectedPerson = new ProtectedPerson();

        $protectedPerson->setDossier($dossier)
            ->setCivility($this->requiredString($data['civility'], 'civility'))
            ->setFirstname($this->requiredString($data['firstname'], 'firstname'))
            ->setLastname($this->requiredString($data['lastname'], 'lastname'))
            ->setBirthDate($this->createDate($data['birth_date'], 'date de naissance'));

        $this->applyOptionalData($protectedPerson, $data);
        
        $dossier->setProtectedPerson($protectedPerson);
        
        $this->em->persist($protectedPerson);
        
        return $protectedPerson;
    }

    public function update(ProtectedPerson $protectedPerson, array $data) : ProtectedPerson
    {
        if (array_key_exists('civility', $data)) {
            $protectedPerson->setCivility(
                $this->requiredString($data['civility'], 'civility')
            );
        }

        if (array_key_exists('firstname', $data)) {
            $protectedPerson->setFirstname(
                $this->requiredString($data['firstname'], 'firstname')
            );
        }

        if (array_key_exists('lastname', $data)) {
            $protectedPerson->setLastname(
                $this->requiredString($data['lastname'], 'lastname')
            );
        }

        if (array_key_exists('birth_date', $data)) {
            $protectedPerson->setBirthDate(
                $this->createDate($data['birth_date'], 'date de naissance')
            );
        }

        $this->applyOptionalData($protectedPerson, $data);

        $protectedPerson->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $this->em->flush();

        return $protectedPerson;
    }

    private function applyOptionalData(ProtectedPerson $protectedPerson, array $data) : void
    {
        if (array_key_exists('photo_url', $data)) {
            $protectedPerson->setPhotoUrl(
                $this->nullableString($data['photo_url'])
            );
        }

        if (array_key_exists('birth_place', $data)) {
            $protectedPerson->setBirthPlace(
                $this->nullableString($data['birth_place'])
            );
        }

        if (array_key_exists('nationality', $data)) {
            $protectedPerson->setNationality(
                $this->nullableString($data['nationality'])
            );
        }

        if (array_key_exists('family_situation', $data)) {
            $protectedPerson->setFamilySituation(
                $this->nullableString(
                    $data['family_situation']
                )
            );
        }

        if (array_key_exists('children_situation', $data)) {
            $protectedPerson->setChildrenSituation(
                $this->validateChildrenSituation(
                    $data['children_situation']
                )
            );
        }

        if (array_key_exists('address', $data)) {
            $protectedPerson->setAddress(
                $this->nullableString($data['address'])
            );
        }

        if (array_key_exists('postal_code', $data)) {
            $protectedPerson->setPostalCode(
                $this->nullableString($data['postal_code'])
            );
        }

        if (array_key_exists('city', $data)) {
            $protectedPerson->setCity(
                $this->nullableString($data['city'])
            );
        }

        if (array_key_exists('phone_number', $data)) {
            $protectedPerson->setPhoneNumber(
                $this->normalizePhoneNumber(
                    $data['phone_number']
                )
            );
        }

        if (array_key_exists('email', $data)) {
            $protectedPerson->setEmail(
                $this->nullableString($data['email'])
            );
        }

        if (array_key_exists('profession', $data)) {
            $protectedPerson->setProfession(
                $this->nullableString($data['profession'])
            );
        }

        if (array_key_exists('autonomy_level', $data)) {
            $protectedPerson->setAutonomyLevel(
                $this->nullableString(
                    $data['autonomy_level']
                )
            );
        }

        if (array_key_exists('situation_summary', $data)) {
            $protectedPerson->setSituationSummary(
                $this->nullableString(
                    $data['situation_summary']
                )
            );
        }

        if (array_key_exists('deceased_at', $data)) {
            $deceasedAt = $data['deceased_at'];

            if ($deceasedAt === null || $deceasedAt === '') {
                $protectedPerson->setDeceasedAt(null);
            } else {
                $protectedPerson->setDeceasedAt(
                    $this->createDate(
                        $deceasedAt,
                        'date de décès'
                    )
                );
            }
        }

        if (array_key_exists('family_note', $data)) {
            $protectedPerson->setFamilyNote(
                $this->nullableString($data['family_note'])
            );
        }
    }

    private function validateRequiredData(array $data) : void
    {
        $requiredFields = [
            'civility',
            'firstname',
            'lastname',
            'birth_date',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data) || trim((string) $data[$field]) === '') {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Le champ "%s" est obligatoire.',
                        $field,
                    )
                );
            }
        }
    }

    private function createDate(mixed $value, string $fieldLabel) : \DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException(
                sprintf(
                    'La %s est invalide.',
                    $fieldLabel
                )
            );
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        $errors = \DateTimeImmutable::getLastErrors();

        $dateHasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if (!$date || $dateHasErrors) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La %s doit respecter le format YYYY-MM-DD.',
                    $fieldLabel
                )
            );
        }

        return $date;
    }

    private function nullableString(mixed $value) : ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizePhoneNumber(mixed $phoneNumber) : ?string
    {
        $phoneNumber = $this->nullableString($phoneNumber);

        if ($phoneNumber === null) {
            return null;
        }

        return preg_replace('/\s+/', '', $phoneNumber);
    }

    private function requiredString(mixed $value, string $fieldLabel) : string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \InvalidArgumentException(
                sprintf(
                    'Le champ "%s" est obligatoire.',
                    $fieldLabel 
                )
            );
        }

        return $value;
    }

    private function validateChildrenSituation(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException(
                'Le nombre d’enfants doit être un nombre entier.'
            );
        }

        $childrenSituation = (int) $value;

        if ($childrenSituation < 0) {
            throw new \InvalidArgumentException(
                'Le nombre d’enfants ne peut pas être négatif.'
            );
        }

        return $childrenSituation;
    }
}