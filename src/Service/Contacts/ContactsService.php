<?php

namespace App\Service\Contacts;

use App\Entity\Contacts;
use App\Entity\ProtectedPerson;
use App\Entity\User;
use App\Enum\ContactCategory;
use App\Enum\ContactType;
use App\Repository\ContactsRepository;
use App\Repository\DossierRepository;
use App\Repository\ProtectedPersonRepository;
use App\Service\Dossier\DossierUserService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides business logic for managing contacts linked to a protected person.
 *
 * Contacts are accessed through a dossier. Before any operation is performed,
 * the service verifies that:
 * - the dossier exists;
 * - the authenticated user has access to the dossier;
 * - a protected person is associated with the dossier.
 *
 * The service is also responsible for:
 * - validating incoming contact data;
 * - creating, updating and deleting contacts;
 * - formatting contact entities for API responses.
 */
class ContactsService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ContactsRepository $contactsRepository,
        private DossierRepository $dossierRepository,
        private ProtectedPersonRepository $protectedPersonRepository,
        private DossierUserService $dossierUserService
    ) {}

    /**
     * Returns all contacts associated with the protected person of a dossier.
     *
     * Contacts can optionally be filtered by category, for example:
     * family, professional or organization.
     *
     * @return Contacts[]
     */
    public function getContactsByDossier(
        int $dossierId,
        User $user,
        ?string $contactCategory = null
    ): array {
        $protectedPerson = $this->getProtectedPersonByDossier(
            $dossierId,
            $user
        );

        return $this->contactsRepository->findByProtectedPerson(
            $protectedPerson,
            $contactCategory
        );
    }

    /**
     * Returns a specific contact belonging to the protected person of a dossier.
     *
     * The contact is searched using both its identifier and the protected
     * person to prevent access to a contact from another dossier.
     *
     * @throws \RuntimeException When the contact cannot be found.
     */
    public function getContact(
        int $dossierId,
        int $contactId,
        User $user
    ): Contacts {
        $protectedPerson = $this->getProtectedPersonByDossier(
            $dossierId,
            $user
        );

        $contact = $this->contactsRepository->findOneByIdAndProtectedPerson(
            $contactId,
            $protectedPerson
        );

        if (!$contact) {
            throw new \RuntimeException('Contact introuvable.');
        }

        return $contact;
    }

    /**
     * Creates a new contact for the protected person associated with a dossier.
     *
     * Required fields are validated before the contact is hydrated and
     * persisted in the database.
     *
     * @throws \InvalidArgumentException When the submitted data is invalid.
     */
    public function createContact(
        int $dossierId,
        array $data,
        User $user
    ): Contacts {
        $protectedPerson = $this->getProtectedPersonByDossier(
            $dossierId,
            $user
        );

        $this->validateRequiredFields($data);

        $contact = new Contacts();
        $contact->setProtectedPerson($protectedPerson);

        $this->hydrateContact($contact, $data);

        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    /**
     * Partially updates an existing contact.
     *
     * Only fields included in the submitted data are modified.
     * Missing fields keep their current values.
     *
     * @throws \InvalidArgumentException When one of the submitted values is invalid.
     */
    public function updateContact(
        int $dossierId,
        int $contactId,
        array $data,
        User $user
    ): Contacts {
        $contact = $this->getContact(
            $dossierId,
            $contactId,
            $user
        );

        $this->validateRequiredFields($data, true);

        $this->hydrateContact($contact, $data);

        $contact->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $contact;
    }

    /**
     * Deletes a contact belonging to the protected person of a dossier.
     */
    public function deleteContact(
        int $dossierId,
        int $contactId,
        User $user
    ): void {
        $contact = $this->getContact(
            $dossierId,
            $contactId,
            $user
        );

        $this->em->remove($contact);
        $this->em->flush();
    }

    /**
     * Converts a contact entity into an API-friendly array.
     *
     * Dates are formatted consistently before being returned to the client.
     */
    public function formatContact(Contacts $contact): array
    {
        return [
            'id' => $contact->getId(),
            'contact_category' => $contact->getContactCategory(),
            'contact_type' => $contact->getContactType(),
            'firstname' => $contact->getFirstname(),
            'lastname' => $contact->getLastname(),
            'organization_name' => $contact->getOrganizationName(),
            'job_function' => $contact->getJobFunction(),
            'profession' => $contact->getProfession(),
            'birth_date' => $contact->getBirthDate()?->format('Y-m-d'),
            'birth_place' => $contact->getBirthPlace(),
            'address' => $contact->getAddress(),
            'phone' => $contact->getPhone(),
            'email' => $contact->getEmail(),
            'identifier' => $contact->getIdentifier(),
            'contact_person' => $contact->getContactPerson(),
            'protection_role' => $contact->getProtectionRole(),
            'relation_type' => $contact->getRelationType(),
            'note' => $contact->getNote(),
            'created_at' => $contact->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $contact->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * Returns the protected person associated with a dossier.
     *
     * This method centralizes dossier lookup, access control and protected
     * person lookup so that every contact operation applies the same checks.
     *
     * @throws \RuntimeException When the dossier does not exist.
     * @throws \RuntimeException When the user cannot access the dossier.
     * @throws \RuntimeException When no protected person is linked to the dossier.
     */
    private function getProtectedPersonByDossier(
        int $dossierId,
        User $user
    ): ProtectedPerson {
        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            throw new \RuntimeException('Dossier introuvable.');
        }

        if (!$this->dossierUserService->userHasAccess($user, $dossier)) {
            throw new \RuntimeException(
                'Vous n’avez pas accès à ce dossier.'
            );
        }

        $protectedPerson = $this->protectedPersonRepository->findOneBy([
            'dossier' => $dossier,
        ]);

        if (!$protectedPerson) {
            throw new \RuntimeException(
                'Aucune personne protégée n’est associée à ce dossier.'
            );
        }

        return $protectedPerson;
    }

    /**
     * Validates fields required for contact creation or partial update.
     *
     * During creation, every required field must be present and non-empty.
     * During a partial update, only submitted required fields are validated.
     *
     * @throws \InvalidArgumentException When a required field is missing or empty.
     */
    private function validateRequiredFields(
        array $data,
        bool $partial = false
    ): void {
        $requiredFields = [
            'contact_category' => 'La catégorie du contact est obligatoire.',
            'contact_type' => 'Le type de contact est obligatoire.',
            'address' => 'L’adresse est obligatoire.',
        ];

        foreach ($requiredFields as $field => $message) {
            $fieldExists = array_key_exists($field, $data);

            if (!$partial && !$fieldExists) {
                throw new \InvalidArgumentException($message);
            }

            if ($fieldExists && trim((string) $data[$field]) === '') {
                throw new \InvalidArgumentException($message);
            }
        }
    }

    /**
     * Applies submitted data to a contact entity.
     *
     * The use of array_key_exists() is intentional:
     * - a missing field is not modified during a PATCH request;
     * - a field explicitly set to null can clear a nullable property.
     *
     * Contact categories, contact types, email addresses and dates are
     * validated before being assigned to the entity.
     *
     * @throws \InvalidArgumentException When a submitted value is invalid.
     */
    private function hydrateContact(Contacts $contact, array $data): void
    {
        if (array_key_exists('contact_category', $data)) {
            if (!ContactCategory::isValid($data['contact_category'])) {
                throw new \InvalidArgumentException(
                    'La catégorie du contact est invalide.'
                );
            }

            $contact->setContactCategory(
                trim($data['contact_category'])
            );
        }

        if (array_key_exists('contact_type', $data)) {
            if (!ContactType::isValid($data['contact_type'])) {
                throw new \InvalidArgumentException(
                    'Le type de contact est invalide.'
                );
            }

            $contact->setContactType(
                trim($data['contact_type'])
            );
        }

        if (array_key_exists('firstname', $data)) {
            $contact->setFirstname(
                $this->getNullableString($data['firstname'])
            );
        }

        if (array_key_exists('lastname', $data)) {
            $contact->setLastname(
                $this->getNullableString($data['lastname'])
            );
        }

        if (array_key_exists('organization_name', $data)) {
            $contact->setOrganizationName(
                $this->getNullableString($data['organization_name'])
            );
        }

        if (array_key_exists('job_function', $data)) {
            $contact->setJobFunction(
                $this->getNullableString($data['job_function'])
            );
        }

        if (array_key_exists('profession', $data)) {
            $contact->setProfession(
                $this->getNullableString($data['profession'])
            );
        }

        if (array_key_exists('birth_date', $data)) {
            $contact->setBirthDate(
                $this->createDate($data['birth_date'])
            );
        }

        if (array_key_exists('birth_place', $data)) {
            $contact->setBirthPlace(
                $this->getNullableString($data['birth_place'])
            );
        }

        if (array_key_exists('address', $data)) {
            $contact->setAddress(
                trim((string) $data['address'])
            );
        }

        if (array_key_exists('phone', $data)) {
            $contact->setPhone(
                $this->getNullableString($data['phone'])
            );
        }

        if (array_key_exists('email', $data)) {
            $email = $this->getNullableString($data['email']);

            if (
                $email !== null
                && !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                throw new \InvalidArgumentException(
                    'L’adresse e-mail du contact est invalide.'
                );
            }

            $contact->setEmail($email);
        }

        if (array_key_exists('identifier', $data)) {
            $contact->setIdentifier(
                $this->getNullableString($data['identifier'])
            );
        }

        if (array_key_exists('contact_person', $data)) {
            $contact->setContactPerson(
                $this->getNullableString($data['contact_person'])
            );
        }

        if (array_key_exists('protection_role', $data)) {
            $contact->setProtectionRole(
                $this->getNullableString($data['protection_role'])
            );
        }

        if (array_key_exists('relation_type', $data)) {
            $contact->setRelationType(
                $this->getNullableString($data['relation_type'])
            );
        }

        if (array_key_exists('note', $data)) {
            $contact->setNote(
                $this->getNullableString($data['note'])
            );
        }
    }

    /**
     * Converts an API date value into a DateTime instance.
     *
     * Empty or null values are converted to null. Non-empty values must use
     * the strict YYYY-MM-DD format.
     *
     * @throws \InvalidArgumentException When the value is not a valid date.
     */
    private function createDate(mixed $value): ?\DateTime
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat(
            '!Y-m-d',
            (string) $value
        );

        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException(
                'La date de naissance doit être au format YYYY-MM-DD.'
            );
        }

        return $date;
    }

    /**
     * Normalizes an optional string value.
     *
     * Null remains null. Empty strings and strings containing only whitespace
     * are converted to null.
     */
    private function getNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}