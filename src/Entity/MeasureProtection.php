<?php

namespace App\Entity;

use App\Repository\MeasureProtectionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeasureProtectionRepository::class)]
class MeasureProtection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $measureType = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $judgmentDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationYears = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tribunalName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tribunalCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cabinetNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'measureProtections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dossier $dossier = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMeasureType(): ?string
    {
        return $this->measureType;
    }

    public function setMeasureType(string $measureType): static
    {
        $this->measureType = $measureType;

        return $this;
    }

    public function getJudgmentDate(): ?\DateTimeImmutable
    {
        return $this->judgmentDate;
    }

    public function setJudgmentDate(\DateTimeImmutable $judgmentDate): static
    {
        $this->judgmentDate = $judgmentDate;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getDurationYears(): ?int
    {
        return $this->durationYears;
    }

    public function setDurationYears(?int $durationYears): static
    {
        $this->durationYears = $durationYears;

        return $this;
    }

    public function getTribunalName(): ?string
    {
        return $this->tribunalName;
    }

    public function setTribunalName(?string $tribunalName): static
    {
        $this->tribunalName = $tribunalName;

        return $this;
    }

    public function getTribunalCity(): ?string
    {
        return $this->tribunalCity;
    }

    public function setTribunalCity(?string $tribunalCity): static
    {
        $this->tribunalCity = $tribunalCity;

        return $this;
    }

    public function getCabinetNumber(): ?string
    {
        return $this->cabinetNumber;
    }

    public function setCabinetNumber(?string $cabinetNumber): static
    {
        $this->cabinetNumber = $cabinetNumber;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDossier(): ?Dossier
    {
        return $this->dossier;
    }

    public function setDossier(?Dossier $dossier): static
    {
        $this->dossier = $dossier;

        return $this;
    }
}
