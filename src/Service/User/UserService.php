<?php

namespace App\Service\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $passwordHasher)
    {
        
    }

    public function getProfile(User $user): array
    {

        $id = $user->getId();
        $email = $user->getEmail();
        $firstname = $user->getFirstname();
        $lastname = $user->getLastname();
        $civility = $user->getCivility();
        $address = $user->getAddress();
        $postalCode = $user->getPostalCode();
        $city = $user->getCity();
        $birthDate = $user->getBirthDate()?->format('Y-m-d');
        $birthPlace = $user->getBirthPlace();
        $nationality = $user->getNationality();
        $phoneNumber = $user->getPhoneNumber();
        $profession = $user->getProfession();
        $practicing = $user->getPracticing();

        return [
            'id' => $id,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'civility' => $civility,
            'address' => $address,
            'postal_code' => $postalCode,
            'city' => $city,
            'birth_date' => $birthDate,
            'birth_place' => $birthPlace,
            'nationality' => $nationality,
            'phone_number' => $phoneNumber,
            'profession' => $profession,
            'practicing' => $practicing,           
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        $user->setCivility($data['civility'] ?? $user->getCivility());
        $user->setFirstname($data['firstname'] ?? $user->getFirstname());
        $user->setLastname($data['lastname'] ?? $user->getLastname());
        $user->setAddress($data['address'] ?? $user->getAddress());
        $user->setPostalCode($data['postal_code'] ?? $user->getPostalCode());
        $user->setCity($data['city'] ?? $user->getCity());

        if (!empty($data['birth_date'])) {
            $user->setBirthDate(new \DateTime($data['birth_date']));
        }

        $user->setBirthPlace($data['birth_place'] ?? $user->getBirthPlace());
        $user->setNationality($data['nationality'] ?? $user->getNationality());
        $user->setPhoneNumber($data['phone_number'] ?? $user->getPhoneNumber());
        $user->setProfession($data['profession'] ?? $user->getProfession());
        $user->setPracticing($data['practicing'] ?? $user->getPracticing());

        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $this->getProfile($user);
    }

    public function updatePassword(User $user, array $data): void
    {
        $isPasswordValid = $this->passwordHasher->isPasswordValid(
            $user,
            $data['current_password'] ?? ''
        );

        if (!$isPasswordValid) {
            throw new \Exception('Le mot de passe actuel est incorrect.');
        }

        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $data['new_password']
            )
        );

        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();
    }
}