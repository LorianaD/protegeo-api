<?php

namespace App\Service\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierUser;
use App\Entity\User;
use App\Enum\DossierUserRole;
use App\Repository\DossierUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class DossierUserService
{
    public function __construct(private EntityManagerInterface $em, private DossierUserRepository $dossierUserRepository)
    {}

    /**
     * Ajoute un utilisateur à un dossier avec son rôle.
     */
    public function addUserToDossier(Dossier $dossier, User $user, string $roleType) : DossierUser
    {
        $roleType = trim($roleType);

        if ($roleType === '') {
            throw new InvalidArgumentException(
                'Le rôle de l’utilisateur dans le dossier est obligatoire.'
            );
        }

        if (!DossierUserRole::isValid($roleType)) {
            throw new InvalidArgumentException(
                'Le rôle renseigné n\'est pas valide.'
            );
        }

        /*
        * Le contrôle du doublon n’est nécessaire que pour
        * un dossier déjà enregistré en base de données.
        */
        if ($dossier->getId() !== null) {
            $existingDossierUser = $this->dossierUserRepository
                ->findOneByUserAndDossier(
                    $user,
                    $dossier
                );

            if ($existingDossierUser !== null) {
                throw new InvalidArgumentException(
                    'Cet utilisateur est déjà associé à ce dossier.'
                );
            }
        }

        $dossierUser = new DossierUser();

        $dossierUser
            ->setDossier($dossier)
            ->setUser($user)
            ->setRoleType($roleType);

        $this->em->persist($dossierUser);

        return $dossierUser;
    }

    /**
     * Modifie le rôle d’un utilisateur dans un dossier.
     */
    public function updateRole(DossierUser $dossierUser, string $roleType) : DossierUser
    {
        $roleType = trim($roleType);

        if ($roleType === '') {
            throw new InvalidArgumentException(
                'Le rôle de l’utilisateur est obligatoire.'
            );
        }

        if (!DossierUserRole::isValid($roleType)) {
            throw new InvalidArgumentException(
                'Le rôle renseigné n\'est pas valide.'
            );
        }

        $dossierUser->setRoleType($roleType);
        
        $this->em->flush();

        return $dossierUser;
    }

    /**
     * Retire un utilisateur d’un dossier.
     */
    public function removeUserFromDossier(DossierUser $dossierUser) : void
    {
        $this->em->remove($dossierUser);
        $this->em->flush();
    }

    /**
     * Vérifie qu’un utilisateur a accès au dossier.
     */
    public function userHasAccess(User $user, Dossier $dossier) : bool
    {
        return $this->dossierUserRepository->userHasAccess(
            $user,
            $dossier
        );
    }

    /**
     * Récupère la liaison entre un utilisateur et un dossier.
     */
    public function getDossierUser(User $user, Dossier $dossier) : DossierUser
    {
        $dossierUser = $this->dossierUserRepository->findOneByUserAndDossier($user, $dossier);

        if ($dossierUser === null) {
            throw new InvalidArgumentException(
                'Cet utilisateur n’est pas associé à ce dossier.'
            );
        }

        return $dossierUser;
    }

    /**
     * Récupère les dossiers ouverts liés à l’utilisateur.
     *
     * @return DossierUser[]
     */
    
    public function getOpenDossiersByUser(User $user) : array
    {
        return $this->dossierUserRepository->findOpenDossiersByUser($user);
    }

    public function getRoleType() : array
    {
        return DossierUserRole::ROLE_TYPES;
    }
}
