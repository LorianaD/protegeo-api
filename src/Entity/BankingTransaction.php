<?php

namespace App\Entity;

use App\Repository\BankingTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BankingTransactionRepository::class)]
class BankingTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 13, scale: 3)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $operationDate = null;

    #[ORM\Column(length: 100)]
    private ?string $movementType = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'sourceBankingTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BankAccount $sourceBankAccount = null;

    #[ORM\ManyToOne(inversedBy: 'destinationBankingTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BankAccount $destinationBankAccount = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getOperationDate(): ?\DateTimeImmutable
    {
        return $this->operationDate;
    }

    public function setOperationDate(\DateTimeImmutable $operationDate): static
    {
        $this->operationDate = $operationDate;

        return $this;
    }

    public function getMovementType(): ?string
    {
        return $this->movementType;
    }

    public function setMovementType(string $movementType): static
    {
        $this->movementType = $movementType;

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

    public function getSourceBankAccount(): ?BankAccount
    {
        return $this->sourceBankAccount;
    }

    public function setSourceBankAccount(?BankAccount $sourceBankAccount): static
    {
        $this->sourceBankAccount = $sourceBankAccount;

        return $this;
    }

    public function getDestinationBankAccount(): ?BankAccount
    {
        return $this->destinationBankAccount;
    }

    public function setDestinationBankAccount(?BankAccount $destinationBankAccount): static
    {
        $this->destinationBankAccount = $destinationBankAccount;

        return $this;
    }
}
