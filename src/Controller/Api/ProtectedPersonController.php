<?php

namespace App\Controller\Api;

use App\Service\Formatter\ProtectedPersonFormatter;
use App\Service\ProtectedPerson\ProtectedPersonPhotoService;
use App\Service\ProtectedPerson\ProtectedPersonService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{id}/protected-person', name: 'api_protected_person_')]
final class ProtectedPersonController extends ApiController
{
    public function __construct(
        private readonly ProtectedPersonService $protectedPersonService,
        private readonly ProtectedPersonFormatter $protectedPersonFormatter,
        private readonly ProtectedPersonPhotoService $protectedPersonPhotoService,
    ) {}

    /**
     * Returns the protected person associated with the dossier.
     */
    #[Route('', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $protectedPerson = $this->protectedPersonService->getByDossierId(
                $id,
                $user
            );

            $protectedPersonData = $this->protectedPersonFormatter->format(
                $protectedPerson
            );

            return $this->json([
                'protected_person' => $protectedPersonData,
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Updates the protected person associated with the dossier.
     */
    #[Route('', name: 'edit', methods: ['PATCH'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $protectedPerson = $this->protectedPersonService->getByDossierId(
                $id,
                $user
            );

            $data = $request->toArray();

            $updatedProtectedPerson = $this->protectedPersonService->update(
                $protectedPerson,
                $data
            );

            $protectedPersonData = $this->protectedPersonFormatter->format(
                $updatedProtectedPerson
            );

            return $this->json([
                'message' => 'La personne protégée a été mise à jour.',
                'protected_person' => $protectedPersonData,
            ], JsonResponse::HTTP_OK);
        } catch (JsonException) {
            return $this->json([
                'message' => 'Le contenu JSON est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
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
     * Returns the protected person's profile photo.
     */
    #[Route('/photo', name: 'photo_show', methods: ['GET'])]
    public function showPhoto(int $id): BinaryFileResponse|JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $protectedPerson = $this->protectedPersonService->getByDossierId(
                $id,
                $user
            );

            $photoPath = $this->protectedPersonPhotoService->getPhotoPath(
                $protectedPerson
            );

            return $this->file($photoPath);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Uploads or replaces the protected person's profile photo.
     */
    #[Route('/photo', name: 'photo_upload', methods: ['POST'])]
    public function uploadPhoto(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $protectedPerson = $this->protectedPersonService->getByDossierId(
                $id,
                $user
            );

            $photo = $request->files->get('photo');

            if (!$photo instanceof UploadedFile) {
                return $this->json([
                    'message' => 'La photo est obligatoire.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $this->protectedPersonPhotoService->upload(
                $protectedPerson,
                $photo
            );

            $protectedPersonData = $this->protectedPersonFormatter->format(
                $protectedPerson
            );

            return $this->json([
                'message' => 'La photo de profil a été mise à jour.',
                'protected_person' => $protectedPersonData,
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
     * Deletes the protected person's profile photo.
     */
    #[Route('/photo', name: 'photo_delete', methods: ['DELETE'])]
    public function deletePhoto(int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $protectedPerson = $this->protectedPersonService->getByDossierId(
                $id,
                $user
            );

            $this->protectedPersonPhotoService->delete(
                $protectedPerson
            );

            $protectedPersonData = $this->protectedPersonFormatter->format(
                $protectedPerson
            );

            return $this->json([
                'message' => 'La photo de profil a été supprimée.',
                'protected_person' => $protectedPersonData,
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
            default => JsonResponse::HTTP_NOT_FOUND,
        };
    }
}