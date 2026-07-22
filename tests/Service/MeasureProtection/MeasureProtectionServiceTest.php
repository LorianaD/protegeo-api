<?php

namespace App\Tests\Service\MeasureProtection;

use App\Entity\Dossier;
use App\Entity\MeasureProtection;
use App\Entity\User;
use App\Repository\MeasureProtectionRepository;
use App\Service\Dossier\DossierService;
use App\Service\MeasureProtection\MeasureProtectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MeasureProtectionServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private MeasureProtectionService $measureProtectionService;
    private MeasureProtectionRepository $measureProtectionRepository;
    private DossierService $dossierService;
    private UserPasswordHasherInterface $passwordHasher;
    private User $user;
    private Dossier $dossier;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        // Retrieve the services required by the test suite.
        $this->em = $container->get(EntityManagerInterface::class);
        $this->measureProtectionService = $container->get(
            MeasureProtectionService::class
        );
        $this->measureProtectionRepository = $container->get(
            MeasureProtectionRepository::class
        );
        $this->dossierService = $container->get(
            DossierService::class
        );
        $this->passwordHasher = $container->get(
            UserPasswordHasherInterface::class
        );

        // Delete measures before deleting their dossiers.
        $this->em->createQuery(
            'DELETE FROM App\Entity\MeasureProtection mp'
        )->execute();

        // Delete protected persons before deleting their dossiers.
        $this->em->createQuery(
            'DELETE FROM App\Entity\ProtectedPerson pp'
        )->execute();

        // Delete dossier-user relationships before deleting dossiers.
        $this->em->createQuery(
            'DELETE FROM App\Entity\DossierUser du'
        )->execute();

        // Delete dossiers created by previous tests.
        $this->em->createQuery(
            'DELETE FROM App\Entity\Dossier d'
        )->execute();

        // Delete the dedicated test user if it already exists.
        $this->em->createQuery(
            'DELETE FROM App\Entity\User u WHERE u.email = :email'
        )
            ->setParameter(
                'email',
                'measure-protection-test@example.com'
            )
            ->execute();

        $this->user = new User();

        $this->user
            ->setEmail('measure-protection-test@example.com')
            ->setCivility('Madame')
            ->setLastname('Test')
            ->setFirstname('MeasureProtection');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $this->user,
            'Password123!'
        );

        $this->user->setPassword($hashedPassword);

        $this->em->persist($this->user);
        $this->em->flush();

        $this->dossier = $this->createDossierForTest();
    }

    public function testCreateMeasureProtection(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->assertNotNull(
            $measureProtection->getId()
        );

        $this->assertSame(
            'Curatelle renforcée',
            $measureProtection->getMeasureType()
        );

        $this->assertSame(
            '2026-07-01',
            $measureProtection->getJudgmentDate()?->format('Y-m-d')
        );

        $this->assertSame(
            '2026-07-15',
            $measureProtection->getStartDate()?->format('Y-m-d')
        );

        $this->assertSame(
            5,
            $measureProtection->getDurationYears()
        );

        $this->assertSame(
            $this->dossier->getId(),
            $measureProtection->getDossier()?->getId()
        );
    }

    public function testCreateWithoutMeasureType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le champ "measure_type" est obligatoire.'
        );

        $this->measureProtectionService->create(
            $this->dossier,
            [
                'judgment_date' => '2026-07-01',
                'start_date' => '2026-07-15',
            ]
        );
    }

    public function testCreateWithoutJudgmentDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le champ "judgment_date" est obligatoire.'
        );

        $this->measureProtectionService->create(
            $this->dossier,
            [
                'measure_type' => 'Curatelle renforcée',
                'start_date' => '2026-07-15',
            ]
        );
    }

    public function testCreateWithoutStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le champ "start_date" est obligatoire.'
        );

        $this->measureProtectionService->create(
            $this->dossier,
            [
                'measure_type' => 'Curatelle renforcée',
                'judgment_date' => '2026-07-01',
            ]
        );
    }

    public function testUpdateMeasureType(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $updatedMeasureProtection = $this
            ->measureProtectionService
            ->update(
                $measureProtection,
                [
                    'measure_type' => 'Tutelle',
                ]
            );

        $this->assertSame(
            'Tutelle',
            $updatedMeasureProtection->getMeasureType()
        );
    }

    public function testUpdateEndDate(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $updatedMeasureProtection = $this
            ->measureProtectionService
            ->update(
                $measureProtection,
                [
                    'end_date' => '2031-07-15',
                ]
            );

        $this->assertSame(
            '2031-07-15',
            $updatedMeasureProtection
                ->getEndDate()
                ?->format('Y-m-d')
        );
    }

    public function testUpdateEndDateToNull(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->measureProtectionService->update(
            $measureProtection,
            [
                'end_date' => '2031-07-15',
            ]
        );

        $updatedMeasureProtection = $this
            ->measureProtectionService
            ->update(
                $measureProtection,
                [
                    'end_date' => null,
                ]
            );

        $this->assertNull(
            $updatedMeasureProtection->getEndDate()
        );
    }

    public function testRejectInvalidMeasureType(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le type de mesure renseigné n\'est pas valide.'
        );

        $this->measureProtectionService->update(
            $measureProtection,
            [
                'measure_type' => 'Type inexistant',
            ]
        );
    }

    public function testRejectInvalidJudgmentDate(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La date du jugement doit respecter le format YYYY-MM-DD.'
        );

        $this->measureProtectionService->update(
            $measureProtection,
            [
                'judgment_date' => '01/07/2026',
            ]
        );
    }

    public function testRejectEndDateBeforeStartDate(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La date de fin ne peut pas être antérieure à la date de début.'
        );

        $this->measureProtectionService->update(
            $measureProtection,
            [
                'end_date' => '2026-07-14',
            ]
        );
    }

    public function testRejectStartDateBeforeJudgmentDate(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La date de début ne peut pas être antérieure à la date du jugement.'
        );

        $this->measureProtectionService->update(
            $measureProtection,
            [
                'start_date' => '2026-06-30',
            ]
        );
    }

    public function testRejectNegativeDurationYears(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La durée de la mesure doit être supérieure à zéro.'
        );

        $this->measureProtectionService->update(
            $measureProtection,
            [
                'duration_years' => -2,
            ]
        );
    }

    public function testRejectInvalidDurationYears(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La durée de la mesure doit être un nombre entier.'
        );

        $this->measureProtectionService->update(
            $measureProtection,
            [
                'duration_years' => 'cinq',
            ]
        );
    }

    public function testGetCurrentMeasureProtection(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $currentMeasureProtection = $this
            ->measureProtectionService
            ->getCurrentByDossierId(
                $this->dossier->getId(),
                $this->user
            );

        $this->assertSame(
            $measureProtection->getId(),
            $currentMeasureProtection->getId()
        );

        $this->assertNull(
            $currentMeasureProtection->getEndDate()
        );
    }

    public function testGetMeasureProtectionsByDossierId(): void
    {
        $measureProtection = $this->createMeasureProtectionForTest(
            $this->dossier
        );

        $measureProtections = $this
            ->measureProtectionService
            ->getByDossierId(
                $this->dossier->getId(),
                $this->user
            );

        $this->assertCount(
            1,
            $measureProtections
        );

        $this->assertSame(
            $measureProtection->getId(),
            $measureProtections[0]->getId()
        );
    }

    /**
     * Creates and persists a dossier used by measure-protection tests.
     */
    private function createDossierForTest(
        string $referenceNumber = 'MP-TEST-001'
    ): Dossier {
        $dossier = $this->dossierService->createDossier(
            [
                'referenceNumber' => $referenceNumber,
                'openedAt' => '2026-07-22',
                'roleType' =>
                    'Curateur / Curatrice à la personne et aux biens',
            ],
            $this->user
        );

        $this->dossierService->save();

        return $dossier;
    }

    /**
     * Creates and persists a measure protection linked to the given dossier.
     */
    private function createMeasureProtectionForTest(Dossier $dossier): MeasureProtection
    {
        $measureProtection = $this->measureProtectionService->create(
            $dossier,
            [
                'measure_type' => 'Curatelle renforcée',
                'judgment_date' => '2026-07-01',
                'start_date' => '2026-07-15',
                'duration_years' => 5,
                'tribunal_name' => 'Tribunal judiciaire',
                'tribunal_city' => 'Bordeaux',
                'cabinet_number' => 'Cabinet 3',
            ]
        );

        $this->em->flush();

        return $measureProtection;
    }
}