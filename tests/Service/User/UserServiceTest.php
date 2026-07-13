<?php

namespace App\Tests\Service\User;

use App\Entity\User;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserService $userService;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->userService = self::getContainer()->get(UserService::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testGetProfileReturnsUserData(): void
    {
        $user = $this->createUser();

        $result = $this->userService->getProfile($user);

        $this->assertSame('Test', $result['firstname']);
        $this->assertSame('TEST', $result['lastname']);
        $this->assertSame($user->getEmail(), $result['email']);
        $this->assertSame('Gradignan', $result['city']);
    }

    public function testUpdateProfileSuccessfully(): void
    {
        $user = $this->createUser();

        $result = $this->userService->updateProfile($user, [
            'city' => 'Aix-en-Provence',
        ]);

        $this->assertSame('Aix-en-Provence', $user->getCity());
        $this->assertSame('Aix-en-Provence', $result['city']);
    }

    public function testUpdatePasswordFailsWhenCurrentPasswordIsWrong(): void
    {
        $user = $this->createUser();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Le mot de passe actuel est incorrect.');

        $this->userService->updatePassword($user, [
            'current_password' => 'WrongPassword',
            'new_password' => 'NewTest1234!',
        ]);
    }

    public function testUpdatePasswordSuccessfully(): void
    {
        $user = $this->createUser();

        $this->userService->updatePassword($user, [
            'current_password' => 'Test1234!1234',
            'new_password' => 'NewTest1234!1234',
        ]);

        $this->assertTrue(
            $this->passwordHasher->isPasswordValid($user, 'NewTest1234!1234')
        );

        $this->assertFalse(
            $this->passwordHasher->isPasswordValid($user, 'Test1234!1234')
        );
    }

    private function createUser(array $override = []): User
    {
        $data = array_merge([
            'email' => uniqid('test-user-', true) . '@example.com',
            'password' => 'Test1234!1234',
            'firstname' => 'Test',
            'lastname' => 'TEST',
            'civility' => 'Madame',
            'address' => 'Résidence Test',
            'postal_code' => '33170',
            'city' => 'Gradignan',
        ], $override);

        $user = new User();
        
        $user->setEmail($data['email']);
        $user->setCivility($data['civility']);
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setAddress($data['address']);
        $user->setPostalCode($data['postal_code']);
        $user->setCity($data['city']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}