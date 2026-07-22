<?php

namespace App\Entity;

use App\Repository\DossierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DossierRepository::class)]
class Dossier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200, unique: true)]
    private ?string $referenceNumber = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $openedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    /**
     * @var Collection<int, DossierUser>
     */
    #[ORM\OneToMany(targetEntity: DossierUser::class, mappedBy: 'dossier')]
    private Collection $dossierUsers;

    #[ORM\OneToOne(mappedBy: 'dossier', targetEntity: ProtectedPerson::class, cascade: ['persist'])]
    private ?ProtectedPerson $protectedPerson = null;

    /**
     * @var Collection<int, MeasureProtection>
     */
    #[ORM\OneToMany(targetEntity: MeasureProtection::class, mappedBy: 'dossier')]
    private Collection $measureProtections;

    public function __construct()
    {
        $this->dossierUsers = new ArrayCollection();
        $this->measureProtections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): static
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    public function getOpenedAt(): ?\DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function setOpenedAt(\DateTimeImmutable $openedAt): static
    {
        $this->openedAt = $openedAt;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * @return Collection<int, DossierUser>
     */
    public function getDossierUsers(): Collection
    {
        return $this->dossierUsers;
    }

    public function addDossierUser(DossierUser $dossierUser): static
    {
        if (!$this->dossierUsers->contains($dossierUser)) {
            $this->dossierUsers->add($dossierUser);
            $dossierUser->setDossier($this);
        }

        return $this;
    }

    public function removeDossierUser(DossierUser $dossierUser): static
    {
        if ($this->dossierUsers->removeElement($dossierUser)) {
            // set the owning side to null (unless already changed)
            if ($dossierUser->getDossier() === $this) {
                $dossierUser->setDossier(null);
            }
        }

        return $this;
    }

    public function getProtectedPerson(): ?ProtectedPerson
    {
        return $this->protectedPerson;
    }

    public function setProtectedPerson(ProtectedPerson $protectedPerson): static
    {
        // set the owning side of the relation if necessary
        if ($protectedPerson->getDossier() !== $this) {
            $protectedPerson->setDossier($this);
        }

        $this->protectedPerson = $protectedPerson;

        return $this;
    }

    /**
     * @return Collection<int, MeasureProtection>
     */
    public function getMeasureProtections(): Collection
    {
        return $this->measureProtections;
    }

    public function addMeasureProtection(MeasureProtection $measureProtection): static
    {
        if (!$this->measureProtections->contains($measureProtection)) {
            $this->measureProtections->add($measureProtection);
            $measureProtection->setDossier($this);
        }

        return $this;
    }

    public function removeMeasureProtection(MeasureProtection $measureProtection): static
    {
        if ($this->measureProtections->removeElement($measureProtection)) {
            // set the owning side to null (unless already changed)
            if ($measureProtection->getDossier() === $this) {
                $measureProtection->setDossier(null);
            }
        }

        return $this;
    }
}
