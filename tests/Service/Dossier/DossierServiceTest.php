<?php

namespace App\Tests\Service\Dossier;

use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\Service\Dossier\DossierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossierServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DossierService $dossierService;
    private DossierRepository $dossierRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->dossierService = $container->get(DossierService::class);
        $this->dossierRepository = $container->get(DossierRepository::class);

        $this->em->createQuery('DELETE FROM App\Entity\Dossier d')
            ->execute();
    }

    // Valid creation test
    public function testCreateDossier() : void
    {
        $data = [
            'referenceNumber' => 'TEST-001',
            'openedAt' => '2026-07-16',
        ];

        $dossier = $this->dossierService->createDossier($data);

        $this->assertNotNull($dossier->getId());
        $this->assertSame('TEST-001', $dossier->getReferenceNumber());
        $this->assertSame(
            '2026-07-16',
            $dossier->getOpenedAt()?->format('Y-m-d')
        );
        $this->assertNull($dossier->getClosedAt());
    }

    // Reference number missing
    public function testCreateDossierWithoutReferenceNumber() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le numéro de référence est obligatoire.'
        );

        $this->dossierService->createDossier([
            'openedAt' => '2026-07-16',
        ]);
    }

    // Number already in use
    public function testCreateDossierWithExistingReferenceNumber(): void
    {
        $data = [
            'referenceNumber' => 'TEST-002',
            'openedAt' => '2026-07-16',
        ];

        $this->dossierService->createDossier($data);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Un dossier avec ce numéro de référence existe déjà.'
        );

        $this->dossierService->createDossier($data);
    }

    // Changing the number
    public function testUpdateDossierReferenceNumber(): void
    {
        $dossier = $this->createDossierForTest('TEST-003');

        $updatedDossier = $this->dossierService->updateDossier(
            $dossier,
            [
                'referenceNumber' => 'TEST-004',
            ]
        );

        $this->assertSame(
            'TEST-004',
            $updatedDossier->getReferenceNumber()
        );
    }

    // Rejection of an already used reference
    public function testUpdateDossierWithExistingReferenceNumber(): void
    {
        $firstDossier = $this->createDossierForTest('TEST-005');
        $secondDossier = $this->createDossierForTest('TEST-006');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Un dossier avec ce numéro de référence existe déjà.'
        );

        $this->dossierService->updateDossier(
            $secondDossier,
            [
                'referenceNumber' => $firstDossier->getReferenceNumber(),
            ]
        );
    }

    // Closure of the case
    public function testCloseDossier(): void
    {
        $dossier = $this->createDossierForTest('TEST-007');

        $updatedDossier = $this->dossierService->updateDossier(
            $dossier,
            [
                'closedAt' => '2026-07-17',
            ]
        );

        $this->assertSame(
            '2026-07-17',
            $updatedDossier->getClosedAt()?->format('Y-m-d')
        );
    }

    // Reopening of the case
    public function testReopenDossier(): void
    {
        $dossier = $this->createDossierForTest('TEST-008');

        $this->dossierService->updateDossier(
            $dossier,
            [
                'closedAt' => '2026-07-17',
            ]
        );

        $updatedDossier = $this->dossierService->updateDossier(
            $dossier,
            [
                'closedAt' => null,
            ]
        );

        $this->assertNull($updatedDossier->getClosedAt());
    }

    // Closing date prior to the opening date
    public function testClosedAtCannotBeBeforeOpenedAt(): void
    {
        $dossier = $this->createDossierForTest('TEST-009');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La date de clôture ne peut pas être antérieure à la date d’ouverture.'
        );

        $this->dossierService->updateDossier(
            $dossier,
            [
                'openedAt' => '2026-07-16',
                'closedAt' => '2026-07-15',
            ]
        );
    }

    private function createDossierForTest(string $referenceNumber): Dossier
    {
        return $this->dossierService->createDossier([
            'referenceNumber' => $referenceNumber,
            'openedAt' => '2026-07-16',
        ]);
    }    
}