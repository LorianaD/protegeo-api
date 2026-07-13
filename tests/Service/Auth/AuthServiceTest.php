<?php

namespace App\Tests\Service\Auth;

use App\Entity\User;
use App\Service\Auth\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private AuthService $authService;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->authService = self::getContainer()->get(AuthService::class);
    }

    public function testRegisterFailsWhenEmailIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Adresse e-mail invalide.');

        $this->authService->register($this->getValidRegisterData([
            'email' => 'test',
        ]));
    }

    public function testRegisterFailsWhenPasswordIsTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 12 caractères.');

        $this->authService->register($this->getValidRegisterData([
            'password' => '123',
        ]));
    }
    
    public function testRegisterFailsWhenEmailAlreadyExists(): void
    {
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setCivility('Madame');
        $user->setFirstname('Test');
        $user->setLastname('TEST');
        $user->setAddress('Résidence Test');
        $user->setPostalCode('33170');
        $user->setCity('Gradignan');
        $user->setPassword($passwordHasher->hashPassword($user, 'Test1234!1234'));

        $this->em->persist($user);
        $this->em->flush();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cette adresse e-mail est déjà utilisée.');

        $this->authService->register($this->getValidRegisterData([
            'email' => 'existing@example.com',
        ]));
    }

    public function testRegisterCreatesUserSuccessfully(): void
    {
        $result = $this->authService->register($this->getValidRegisterData([
            'email' => 'new-user@example.com',
        ]));

        $this->assertSame('new-user@example.com', $result['email']);
        $this->assertSame('TEST', $result['lastname']);
        $this->assertSame('Test', $result['firstname']);
        $this->assertArrayHasKey('id', $result);
    }

    private function getValidRegisterData(array $override = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'Test1234!1234',
            'firstname' => 'Test',
            'lastname' => 'TEST',
            'civility' => 'Madame',
            'address' => 'Résidence Test',
            'postal_code' => '33170',
            'city' => 'Gradignan',
        ], $override);
    }
}