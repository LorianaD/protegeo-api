<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\DossierRepository;
use App\Repository\MeasureProtectionRepository;
use App\Service\Formatter\MeasureProtectionFormatter;
use App\Service\MeasureProtection\MeasureProtectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Exception\JsonException;

#[Route('/api/dossiers/{id}/measure-protections', name: 'api_measure_protection_')]
class MeasureProtectionController extends AbstractController
{
    public function __construct(
        private readonly MeasureProtectionService $measureProtectionService,
        private readonly MeasureProtectionRepository $measureProtectionRepository,
        private readonly DossierRepository $dossierRepository,
        private readonly EntityManagerInterface $em,
        private readonly MeasureProtectionFormatter $measureProtectionFormatter,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $measureProtections = $this
                ->measureProtectionService
                ->getByDossierId($id, $user);

            return $this->json([
                'measureProtections' => $this
                    ->measureProtectionFormatter
                    ->formatCollection($measureProtections),
            ], 200);
        } catch (\Exception $exception) {
            return $this->json([
                'message' => 'Une erreur est survenue lors de la récupération des mesures de protection.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    #[Route('/current', name: 'current', methods: ['GET'])]
    public function current(int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $measureProtection = $this->measureProtectionService->getCurrentByDossierId(
                $id,
                $user
            );

            return $this->json([
                'measureProtection' =>
                    $this->measureProtectionFormatter->format(
                        $measureProtection
                    ),
            ], 200);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 404);
        } catch (\Exception $exception) {
            return $this->json([
                'message' => 'Une erreur est survenue lors de la récupération de la mesure de protection.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    #[Route('', name: 'new', methods: ['POST'])]
    public function new(int $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $dossier = $this->dossierRepository->findOneByIdAndUser(
                $id,
                $user
            );

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable ou accès refusé.',
                ], 404);
            }

            $data = $request->toArray();

            $measureProtection = $this->measureProtectionService->create(
                $dossier,
                $data
            );

            $this->em->flush();

            return $this->json([
                'message' => 'La mesure de protection a été créée avec succès.',
                'measureProtection' => $this->measureProtectionFormatter->format(
                    $measureProtection
                ),
            ], 201);
        } catch (JsonException) {
            return $this->json([
                'message' => 'Le contenu JSON est invalide.',
            ], 400);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (\Exception $exception) {
            return $this->json([
                'message' => 'Une erreur est survenue lors de la création de la mesure de protection.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    #[Route('/{measureId}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, int $measureId, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non connecté.',
            ], 401);
        }

        try {
            $measureProtection = $this->measureProtectionRepository->findOneByIdAndDossierIdAndUser(
                $measureId,
                $id,
                $user
            );

            if (!$measureProtection) {
                return $this->json([
                    'message' => 'Mesure de protection introuvable ou accès refusé.',
                ], 404);
            }

            $data = $request->toArray();

            $measureProtection = $this->measureProtectionService->update(
                $measureProtection,
                $data
            );

            return $this->json([
                'message' => 'La mesure de protection a été modifiée avec succès.',
                'measureProtection' =>
                    $this->measureProtectionFormatter->format(
                        $measureProtection
                    ),
            ], 200);
        } catch (JsonException) {
            return $this->json([
                'message' => 'Le contenu JSON est invalide.',
            ], 400);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (\Exception $exception) {
            return $this->json([
                'message' => 'Une erreur est survenue lors de la modification de la mesure de protection.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
