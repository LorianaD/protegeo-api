<?php

namespace App\Controller\Api;

use App\Repository\DossierRepository;
use App\Repository\MeasureProtectionRepository;
use App\Service\Formatter\MeasureProtectionFormatter;
use App\Service\MeasureProtection\MeasureProtectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers/{id}/measure-protections', name: 'api_measure_protection_')]
class MeasureProtectionController extends ApiController
{
    public function __construct(
        private MeasureProtectionService $measureProtectionService,
        private MeasureProtectionRepository $measureProtectionRepository,
        private DossierRepository $dossierRepository,
        private EntityManagerInterface $em,
        private MeasureProtectionFormatter $measureProtectionFormatter,
    ) {}

    /**
     * Returns all protection measures associated with the dossier.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $measureProtections = $this->measureProtectionService->getByDossierId($id, $user);

            $measureProtectionsData = $this->measureProtectionFormatter->formatCollection(
                $measureProtections
            );

            return $this->json([
                'measure_protections' => $measureProtectionsData,
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Returns the current protection measure associated with the dossier.
     */
    #[Route('/current', name: 'current', methods: ['GET'])]
    public function current(int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $measureProtection = $this->measureProtectionService->getCurrentByDossierId(
                $id,
                $user
            );

            $measureProtectionData = $this->measureProtectionFormatter->format(
                $measureProtection
            );

            return $this->json([
                'measure_protection' => $measureProtectionData,
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Returns the latest protection measure associated with the dossier.
     */
    #[Route('/latest', name: 'latest', methods: ['GET'])]
    public function latest(int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $measureProtection = $this->measureProtectionService
                ->getLatestByDossierId(
                    $id,
                    $user
                );

            $measureProtectionData = $this->measureProtectionFormatter->format(
                $measureProtection
            );

            return $this->json([
                'measure_protection' => $measureProtectionData,
            ], JsonResponse::HTTP_OK);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], $this->getRuntimeStatusCode($exception));
        }
    }

    /**
     * Creates a new protection measure for the dossier.
     */
    #[Route('', name: 'new', methods: ['POST'])]
    public function new(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $dossier = $this->dossierRepository->findOneByIdAndUser($id, $user);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable ou accès refusé.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $data = $request->toArray();

            $measureProtection = $this->measureProtectionService->create(
                $dossier,
                $data
            );

            $this->em->flush();

            $measureProtectionData = $this->measureProtectionFormatter->format(
                $measureProtection
            );

            return $this->json([
                'message' => 'La mesure de protection a été créée avec succès.',
                'measure_protection' => $measureProtectionData,
            ], JsonResponse::HTTP_CREATED);
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
     * Updates an existing protection measure.
     */
    #[Route('/{measureId}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, int $measureId, Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser();

            $measureProtection = $this->measureProtectionRepository->findOneByIdAndDossierIdAndUser(
                $measureId,
                $id,
                $user
            );

            if (!$measureProtection) {
                return $this->json([
                    'message' => 'Mesure de protection introuvable ou accès refusé.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $data = $request->toArray();

            $measureProtection = $this->measureProtectionService->update(
                $measureProtection,
                $data
            );

            $measureProtectionData = $this->measureProtectionFormatter->format(
                $measureProtection
            );

            return $this->json([
                'message' => 'La mesure de protection a été modifiée avec succès.',
                'measure_protection' => $measureProtectionData,
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