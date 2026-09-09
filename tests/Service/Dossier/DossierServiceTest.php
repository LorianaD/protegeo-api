<?php

namespace App\Tests\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\User;
use App\Enum\DossierUserRole;
use App\Repository\DossierRepository;
use App\Repository\DossierUserRepository;
use App\Repository\UserRepository;
use App\Service\Dossier\DossierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DossierServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DossierService $dossierService;
    private DossierRepository $dossierRepository;
    private DossierUserRepository $dossierUserRepository;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->dossierService = $container->get(DossierService::class);
        $this->dossierRepository = $container->get(DossierRepository::class);
        $this->dossierUserRepository = $container->get(DossierUserRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Remove dossier-user relationships before deleting dossiers.
        $this->em->createQuery(
            'DELETE FROM App\Entity\DossierUser du'
        )->execute();

        // Remove dossiers created by previous tests.
        $this->em->createQuery(
            'DELETE FROM App\Entity\Dossier d'
        )->execute();

        // Remove the dedicated test user if it already exists.
        $this->em->createQuery(
            'DELETE FROM App\Entity\User u WHERE u.email = :email'
        )
            ->setParameter('email', 'dossier-test@example.com')
            ->execute();

        // Create the user account used throughout the test suite.
        $this->user = new User();

        $this->user
            ->setEmail('dossier-test@example.com')
            ->setCivility('Mme')
            ->setLastname('Test')
            ->setFirstname('Dossier');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $this->user,
            'Password123!'
        );

        $this->user->setPassword($hashedPassword);

        $this->em->persist($this->user);
        $this->em->flush();
    }

    public function testCreateDossier(): void
    {
        $data = [
            'referenceNumber' => 'TEST-001',
            'openedAt' => '2026-07-16',
            'roleType' => DossierUserRole::CURATOR_PERSON_AND_PROPERTY,
        ];

        $dossier = $this->dossierService->createDossier(
            $data,
            $this->user
        );

        $this->dossierService->save();

        $this->assertNotNull($dossier->getId());
        $this->assertSame('TEST-001', $dossier->getReferenceNumber());
        $this->assertSame(
            '2026-07-16',
            $dossier->getOpenedAt()?->format('Y-m-d')
        );
        $this->assertNull($dossier->getClosedAt());
    }

    public function testCreateDossierFailsWithoutReferenceNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le numéro de référence est obligatoire.'
        );

        $this->dossierService->createDossier(
            [
                'openedAt' => '2026-07-16',
                'roleType' => DossierUserRole::CURATOR_PERSON_AND_PROPERTY,
            ],
            $this->user
        );
    }

    public function testCreateDossierFailsWithExistingReferenceNumber(): void
    {
        $data = [
            'referenceNumber' => 'TEST-002',
            'openedAt' => '2026-07-16',
            'roleType' => DossierUserRole::CURATOR_PERSON_AND_PROPERTY,
        ];

        $this->dossierService->createDossier(
            $data,
            $this->user
        );

        $this->dossierService->save();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Un dossier avec ce numéro de référence existe déjà.'
        );

        $this->dossierService->createDossier(
            $data,
            $this->user
        );
    }

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

    public function testUpdateDossierFailsWithExistingReferenceNumber(): void
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

        $this->assertNull(
            $updatedDossier->getClosedAt()
        );
    }

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

    /**
     * Creates and persists a dossier used by the test suite.
     */
    private function createDossierForTest(string $referenceNumber): Dossier
    {
        $dossier = $this->dossierService->createDossier(
            [
                'referenceNumber' => $referenceNumber,
                'openedAt' => '2026-07-16',
                'roleType' => DossierUserRole::CURATOR_PERSON_AND_PROPERTY,
            ],
            $this->user
        );

        $this->dossierService->save();

        return $dossier;
    }
}