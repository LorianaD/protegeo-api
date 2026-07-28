<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Contacts\ContactsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles contact-related API endpoints for a specific dossier.
 *
 * Each contact is linked to the protected person associated with the dossier.
 * Access control and business rules are delegated to the ContactsService.
 *
 * Available operations:
 * - list contacts, optionally filtered by category;
 * - retrieve a specific contact;
 * - create a contact;
 * - partially update a contact;
 * - delete a contact.
 */
#[Route('/api/dossiers/{dossierId}/contacts')]
class ContactsController extends AbstractController
{
    public function __construct(
        private ContactsService $contactsService,
    ) {}

    /**
     * Returns all contacts associated with the given dossier.
     *
     * The optional "contact_category" query parameter can be used to filter
     * contacts by dashboard section, such as family, professional or organization.
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

            return $this->json([
                'contacts' => array_map(
                    fn ($contact) => $this->contactsService->formatContact($contact),
                    $contacts
                ),
            ]);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Returns a single contact belonging to the given dossier.
     *
     * The service verifies that the authenticated user can access the dossier
     * and that the requested contact belongs to its protected person.
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

            return $this->json(
                $this->contactsService->formatContact($contact)
            );
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Creates a new contact for the protected person linked to the dossier.
     *
     * Request data is validated and processed by the ContactsService before
     * the contact is persisted.
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

            return $this->json([
                'message' => 'Le contact a été créé avec succès.',
                'contact' => $this->contactsService->formatContact($contact),
            ], 201);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Partially updates an existing contact.
     *
     * Only fields included in the PATCH request are updated. Missing fields
     * keep their current values.
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

            return $this->json([
                'message' => 'Le contact a été modifié avec succès.',
                'contact' => $this->contactsService->formatContact($contact),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Deletes a contact from the given dossier.
     *
     * Access rights and contact ownership are checked by the service before
     * the contact is removed.
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
            ]);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Returns the currently authenticated application user.
     *
     * @throws \RuntimeException When no valid User instance is authenticated.
     */
    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new \RuntimeException(
                'Utilisateur non authentifié.'
            );
        }

        return $user;
    }

    /**
     * Converts known runtime exceptions into the appropriate HTTP status code.
     *
     * Authentication failures return 401, access-denied errors return 403,
     * and missing dossier or contact resources return 404.
     */
    private function getRuntimeStatusCode(\RuntimeException $exception): int
    {
        return match ($exception->getMessage()) {
            'Utilisateur non authentifié.' => 401,

            'Vous n’avez pas accès à ce dossier.' => 403,

            default => 404,
        };
    }
}
