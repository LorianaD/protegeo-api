<?php

namespace App\Service\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{

    public function __construct( private EntityManagerInterface $em, private UserPasswordHasherInterface $passwordHasher, private UserRepository $userRepository )
    {
    }

    public function register(array $data): array
    {

        $this->validateRegisterData($data);

        $email = strtolower(trim($data['email']));

        $this->validateEmail($email);
        $this->validatePassword($data['password']);
        $this->checkEmailIsAvailable($email);

        $user = new User();

        $user->setEmail($data['email']);
        $user->setCivility($data['civility']);
        $user->setLastname($data['lastname']);
        $user->setFirstname($data['firstname']);
        $user->setAddress($data['address']);
        $user->setPostalCode($data['postal_code']);
        $user->setCity($data['city']);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );

        $this->em->persist($user);
        $this->em->flush();

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'lastname' => $user->getLastname(),
            'firstname' => $user->getFirstname(),
        ];
    }

    private function validateRegisterData(array $data)
    {
        $requiredFields = [
            'email',
            'password',
            'firstname',
            'lastname',
            'civility',
            'address',
            'postal_code',
            'city',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw new \InvalidArgumentException(
                    sprintf('Le champ "%s" est obligatoire.', $field)
                );
            }
        }
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                'Adresse e-mail invalide.'
            );
        }
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 12) {
            throw new \InvalidArgumentException(
                'Le mot de passe doit contenir au moins 12 caractères.'
            );
        }
    }

    private function checkEmailIsAvailable(string $email): void
    {
        if ($this->userRepository->findOneByEmail($email)) {
            throw new \InvalidArgumentException(
                'Cette adresse e-mail est déjà utilisée.'
            );
        }
    }
}