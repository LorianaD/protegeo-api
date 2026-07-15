<?php

namespace App\Controller\Api;

use App\Repository\DossierRepository;
use App\Service\Dossier\DossierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dossiers', name: 'api_dossiers_')]
final class DossierController extends AbstractController
{

    public function __construct(
        private DossierService $dossierService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(DossierRepository $dossierRepository): JsonResponse
    {

        $dossiers = $dossierRepository->findOpenDossiers();

        return $this->json(
            $dossiers,
        );
    }

    #[Route('', name: 'new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {

        try {
            $data = $request->toArray();

            $dossier = $this->dossierService->createDossier($data);

            return $this->json([
                'message' => 'Le dossier a été créé.',
                'dossier' => [
                    'id' => $dossier->getId(),
                    'referenceNumber' => $dossier->getReferenceNumber(),
                    'openedAt' => $dossier->getOpenedAt()?->format('Y-m-d'),
                    'closedAt' => $dossier->getClosedAt()?->format('Y-m-d'),
                ],
            ], 201);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        } // catch (\Exception) {
        //     return $this->json([
        //         'message' => 'Une erreur est survenue lors de la création du dossier.',
        //     ], 500);
        // }

    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id) : JsonResponse
    {

        $dossier = $this->dossierService->showDossier($id);

        if (!$dossier) {
            return $this->json([
                'message' => 'Dossier introuvable',
            ], 404);
        }

        $id = $dossier->getId();
        $referenceNumber = $dossier->getReferenceNumber();
        $openedAt = $dossier->getOpenedAt()?->format('Y-m-d');
        $closedAt = $dossier->getClosedAt()?->format('Y-m-d');

        return $this->json([
            'id' => $id,
            'referenceNumber' => $referenceNumber,
            'openedAt' => $openedAt,
            'closedAt' => $closedAt,
        ]);
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'])]
    public function edit(int $id, Request $request) : JsonResponse
    {
        try {
            $dossier = $this->dossierService->showDossier($id);

            if (!$dossier) {
                return $this->json([
                    'message' => 'Dossier introuvable',
                ], 404);
            }

            $data = $request->toArray();

            $updatedDossier = $this->dossierService->updateDossier($dossier, $data);

            $id = $updatedDossier->getId();
            $referenceNumber = $updatedDossier->getReferenceNumber();
            $openedAt = $updatedDossier->getOpenedAt()?->format('Y-m-d');
            $closedAt = $updatedDossier->getClosedAt()?->format('Y-m-d');

            return $this->json([
                    'message' => 'Le dossier a été mis à jours.',
                    'dossier' => [
                        'id' => $id,
                        'referenceNumber' => $referenceNumber,
                        'openedAt' => $openedAt,
                        'closedAt' => $closedAt,
                    ],
            ], 200);

        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }
}
