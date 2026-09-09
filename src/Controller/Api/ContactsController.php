<?php

namespace App\Controller\Api;

use App\Entity\Contacts;
use App\Service\Contacts\ContactsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles contact-related API endpoints for a specific dossier.
 *
 * Each contact is linked to the protected person associated with the dossier.
 * Access control and business rules are delegated to the ContactsService.
 */
#[Route('/api/dossiers/{dossierId}/contacts')]
class ContactsController extends ApiController
{
    public function __construct(
        private ContactsService $contactsService,
    ) {}

    /**
     * Returns all contacts associated with the given dossier.
     */
    #[Route('', name: 'api_contacts_index', methods: ['GET'])]
    public function index(int $dossierId, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $contactCategory = $request->query->get('contact_category');

            $contacts = $this->contactsService->getContactsByDossier(
                $dossierId,
                $user,
                $contactCategory
            );

            $contactsData = array_map(
                fn (Contacts $contact): array => $this->formatContact($contact),
                $contacts
            );

            return $this->json([
                'contacts' => $contactsData,
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Returns one contact belonging to the given dossier.
     */
    #[Route('/{contactId}', name: 'api_contacts_show', methods: ['GET'])]
    public function show(int $dossierId, int $contactId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $contact = $this->contactsService->getContact(
                $dossierId,
                $contactId,
                $user
            );

            $contactData = $this->formatContact($contact);

            return $this->json(
                $contactData,
                JsonResponse::HTTP_OK
            );
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Creates a new contact for the protected person linked to the dossier.
     */
    #[Route('', name: 'api_contacts_create', methods: ['POST'])]
    public function create(int $dossierId, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();
            $data = $request->toArray();

            $contact = $this->contactsService->createContact(
                $dossierId,
                $data,
                $user
            );

            $contactData = $this->formatContact($contact);

            return $this->json([
                'message' => 'Le contact a été créé avec succès.',
                'contact' => $contactData,
            ], JsonResponse::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Partially updates an existing contact.
     */
    #[Route('/{contactId}', name: 'api_contacts_update', methods: ['PATCH'])]
    public function update(int $dossierId, int $contactId, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();
            $data = $request->toArray();

            $contact = $this->contactsService->updateContact(
                $dossierId,
                $contactId,
                $data,
                $user
            );

            $contactData = $this->formatContact($contact);

            return $this->json([
                'message' => 'Le contact a été modifié avec succès.',
                'contact' => $contactData,
            ], JsonResponse::HTTP_OK);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Deletes a contact from the given dossier.
     */
    #[Route('/{contactId}', name: 'api_contacts_delete', methods: ['DELETE'])]
    public function delete(int $dossierId, int $contactId): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $this->contactsService->deleteContact(
                $dossierId,
                $contactId,
                $user
            );

            return $this->json([
                'message' => 'Le contact a été supprimé avec succès.',
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Converts known runtime exceptions into the appropriate HTTP status code.
     */
    private function getRuntimeStatusCode(\RuntimeException $exception): int
    {
        return match ($exception->getMessage()) {
            'Utilisateur non authentifié.' => JsonResponse::HTTP_UNAUTHORIZED,
            'Vous n’avez pas accès à ce dossier.' => JsonResponse::HTTP_FORBIDDEN,
            default => JsonResponse::HTTP_NOT_FOUND,
        };
    }

    /**
     * Formats a contact for the API response.
     */
    private function formatContact(Contacts $contact): array
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
}