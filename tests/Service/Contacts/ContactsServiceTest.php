<?php

namespace App\Tests\Service\Contacts;

use App\Entity\Contacts;
use App\Entity\Dossier;
use App\Entity\ProtectedPerson;
use App\Entity\User;
use App\Enum\ContactCategory;
use App\Enum\ContactType;
use App\Repository\ContactsRepository;
use App\Repository\DossierRepository;
use App\Repository\ProtectedPersonRepository;
use App\Service\Contacts\ContactsService;
use App\Service\Dossier\DossierUserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ContactsServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ContactsRepository $contactsRepository;
    private DossierRepository $dossierRepository;
    private ProtectedPersonRepository $protectedPersonRepository;
    private DossierUserService $dossierUserService;
    private ContactsService $contactsService;

    protected function setUp(): void
    {
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->contactsRepository = $this->createStub(ContactsRepository::class);
        $this->dossierRepository = $this->createStub(DossierRepository::class);
        $this->protectedPersonRepository = $this->createStub(ProtectedPersonRepository::class);
        $this->dossierUserService = $this->createStub(DossierUserService::class);

        $this->contactsService = new ContactsService(
            $this->em,
            $this->contactsRepository,
            $this->dossierRepository,
            $this->protectedPersonRepository,
            $this->dossierUserService
        );
    }

    public function testCreateContactFailsWhenDossierDoesNotExist(): void
    {
        $user = new User();

        $this->dossierRepository
            ->method('find')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dossier introuvable.');

        $this->contactsService->createContact(
            1,
            [],
            $user
        );
    }

    public function testCreateContactFailsWhenUserHasNoAccess(): void
    {
        $user = new User();
        $dossier = new Dossier();

        $this->dossierRepository
            ->method('find')
            ->willReturn($dossier);

        $this->dossierUserService
            ->method('userHasAccess')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Vous n’avez pas accès à ce dossier.'
        );

        $this->contactsService->createContact(
            1,
            [],
            $user
        );
    }

    public function testCreateContactFailsWhenProtectedPersonDoesNotExist(): void
    {
        $user = new User();
        $dossier = new Dossier();

        $this->dossierRepository
            ->method('find')
            ->willReturn($dossier);

        $this->dossierUserService
            ->method('userHasAccess')
            ->willReturn(true);

        $this->protectedPersonRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Aucune personne protégée n’est associée à ce dossier.'
        );

        $this->contactsService->createContact(
            1,
            [],
            $user
        );
    }

    public function testCreateContactFailsWhenRequiredFieldIsMissing(): void
    {
        $user = new User();
        $dossier = new Dossier();
        $protectedPerson = new ProtectedPerson();

        $this->prepareAccessibleDossier(
            $dossier,
            $protectedPerson
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La catégorie du contact est obligatoire.'
        );

        $this->contactsService->createContact(
            1,
            [
                'contact_type' => ContactType::FATHER,
                'address' => '1 rue de test',
            ],
            $user
        );
    }

    public function testCreateContactFailsWhenEmailIsInvalid(): void
    {
        $user = new User();
        $dossier = new Dossier();
        $protectedPerson = new ProtectedPerson();

        $this->prepareAccessibleDossier(
            $dossier,
            $protectedPerson
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'L’adresse e-mail du contact est invalide.'
        );

        $this->contactsService->createContact(
            1,
            [
                'contact_category' => ContactCategory::FAMILY,
                'contact_type' => ContactType::FATHER,
                'address' => '1 rue de test',
                'email' => 'email-invalide',
            ],
            $user
        );
    }

    public function testCreateContactSuccessfully(): void
    {
        $user = new User();
        $dossier = new Dossier();
        $protectedPerson = new ProtectedPerson();

        $this->prepareAccessibleDossier(
            $dossier,
            $protectedPerson
        );

        $contact = $this->contactsService->createContact(
            1,
            [
                'contact_category' => ContactCategory::FAMILY,
                'contact_type' => ContactType::FATHER,
                'address' => '1 rue de test',
                'firstname' => 'Jean',
                'lastname' => 'Dupont',
                'email' => 'jean.dupont@example.com',
            ],
            $user
        );

        $this->assertInstanceOf(Contacts::class, $contact);
        $this->assertSame(ContactCategory::FAMILY, $contact->getContactCategory());
        $this->assertSame(ContactType::FATHER, $contact->getContactType());
        $this->assertSame('Jean', $contact->getFirstname());
        $this->assertSame('Dupont', $contact->getLastname());
        $this->assertSame('jean.dupont@example.com', $contact->getEmail());
        $this->assertSame('1 rue de test', $contact->getAddress());
    }

    /**
     * Prepares an accessible dossier with its protected person.
     */
    private function prepareAccessibleDossier(
        Dossier $dossier,
        ProtectedPerson $protectedPerson
    ): void {
        $this->dossierRepository
            ->method('find')
            ->willReturn($dossier);

        $this->dossierUserService
            ->method('userHasAccess')
            ->willReturn(true);

        $this->protectedPersonRepository
            ->method('findOneBy')
            ->willReturn($protectedPerson);
    }
}