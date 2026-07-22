<?php

namespace App\Tests\Service\ProtectedPerson;

use App\Entity\Dossier;
use App\Entity\ProtectedPerson;
use App\Entity\User;
use App\Repository\ProtectedPersonRepository;
use App\Service\Dossier\DossierService;
use App\Service\ProtectedPerson\ProtectedPersonService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProtectedPersonServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProtectedPersonService $protectedPersonService;
    private ProtectedPersonRepository $protectedPersonRepository;
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
        $this->protectedPersonService = $container->get(
            ProtectedPersonService::class
        );
        $this->protectedPersonRepository = $container->get(
            ProtectedPersonRepository::class
        );
        $this->dossierService = $container->get(
            DossierService::class
        );
        $this->passwordHasher = $container->get(
            UserPasswordHasherInterface::class
        );

        // Delete protected persons first to respect database relationships.
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
                'protected-person-test@example.com'
            )
            ->execute();

        // Create the authenticated user used throughout the test suite.
        $this->user = new User();

        $this->user
            ->setEmail('protected-person-test@example.com')
            ->setCivility('Madame')
            ->setLastname('Test')
            ->setFirstname('ProtectedPerson');

        $hashedPassword = $this->passwordHasher->hashPassword(
            $this->user,
            'Password123!'
        );

        $this->user->setPassword($hashedPassword);

        $this->em->persist($this->user);
        $this->em->flush();

        // Create the dossier required by protected-person tests.
        $this->dossier = $this->createDossierForTest();
    }

    public function testCreateProtectedPerson(): void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $this->assertNotNull(
            $protectedPerson->getId()
        );

        $this->assertSame(
            'Madame',
            $protectedPerson->getCivility()
        );

        $this->assertSame(
            'Jeanne',
            $protectedPerson->getFirstname()
        );

        $this->assertSame(
            'Dupont',
            $protectedPerson->getLastname()
        );

        $this->assertSame(
            '1980-05-15',
            $protectedPerson->getBirthDate()?->format('Y-m-d')
        );

        $this->assertSame(
            $this->dossier->getId(),
            $protectedPerson->getDossier()?->getId()
        );
    }

    public function testUpdateFirstname(): void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $updatedProtectedPerson = $this->protectedPersonService->update(
            $protectedPerson,
            [
                'firstname' => 'Marie',
            ]
        );

        $this->assertSame(
            'Marie',
            $updatedProtectedPerson->getFirstname()
        );
    }

    public function testRejectNegativeChildrenSituation(): void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le nombre d’enfants ne peut pas être négatif.'
        );

        $this->protectedPersonService->update(
            $protectedPerson,
            [
                'children_situation' => -1,
            ]
        );
    }

    public function testUpdateChildrenSituationToNull() : void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $this->protectedPersonService->update(
            $protectedPerson,
            [
                'children_situation' => 2,
            ]
        );

        $updatedProtectedPerson = $this->protectedPersonService->update(
            $protectedPerson,
            [
                'children_situation' => null,
            ]
        );

        $this->assertNull(
            $updatedProtectedPerson->getChildrenSituation()
        );
    }

    public function testRejectInvalidChildrenSituation(): void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le nombre d’enfants doit être un nombre entier.'
        );

        $this->protectedPersonService->update(
            $protectedPerson,
            [
                'children_situation' => 'deux',
            ]
        );
    }

    public function testRejectInvalidBirthDate() : void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La date de naissance doit respecter le format YYYY-MM-DD.'
        );

        $this->protectedPersonService->update(
            $protectedPerson,
            [
                'birth_date' => '15/05/1980',
            ]
        );
    }

    public function testUpdatePhoneNumber() : void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $updatedProtectedPerson = $this->protectedPersonService->update(
            $protectedPerson,
            [
                'phone_number' => '06 30 39 34 32',
            ]
        );

        $this->assertSame(
            '0630393432',
            $updatedProtectedPerson->getPhoneNumber()
        );
    }

    public function testUpdateFamilyNoteToNull() : void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $this->protectedPersonService->update(
            $protectedPerson,
            [
                'family_note' => 'Note familiale de test.',
            ]
        );

        $updatedProtectedPerson = $this->protectedPersonService->update(
            $protectedPerson,
            [
                'family_note' => null,
            ]
        );

        $this->assertNull(
            $updatedProtectedPerson->getFamilyNote()
        );
    }

    public function testGetProtectedPersonByDossierId(): void
    {
        $protectedPerson = $this->createProtectedPersonForTest(
            $this->dossier
        );

        $foundProtectedPerson = $this->protectedPersonService
            ->getByDossierId(
                $this->dossier->getId(),
                $this->user
            );

        $this->assertSame(
            $protectedPerson->getId(),
            $foundProtectedPerson->getId()
        );

        $this->assertSame(
            'Jeanne',
            $foundProtectedPerson->getFirstname()
        );
    }

    /**
     * Creates and persists a dossier used by protected-person tests.
     */
    private function createDossierForTest(string $referenceNumber = 'PP-TEST-001') : Dossier
    {
        $dossier = $this->dossierService->createDossier(
            [
                'referenceNumber' => $referenceNumber,
                'openedAt' => '2026-07-22',
                'roleType' => 'Curateur / Curatrice à la personne et aux biens',
            ],
            $this->user
        );

        $this->dossierService->save();

        return $dossier;
    }

    /**
     * Creates and persists a protected person linked to the given dossier.
     */
    private function createProtectedPersonForTest(Dossier $dossier): ProtectedPerson
    {
        $protectedPerson = $this->protectedPersonService->create(
            $dossier,
            [
                'civility' => 'Madame',
                'firstname' => 'Jeanne',
                'lastname' => 'Dupont',
                'birth_date' => '1980-05-15',
            ]
        );

        $this->em->flush();

        return $protectedPerson;
    }
}