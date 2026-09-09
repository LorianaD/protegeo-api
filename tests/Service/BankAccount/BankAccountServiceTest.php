<?php

namespace App\Tests\Service\BankAccount;

use App\Entity\BankAccount;
use App\Entity\Dossier;
use App\Repository\BankAccountRepository;
use App\Service\BankAccount\BankAccountService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class BankAccountServiceTest extends TestCase
{
    private BankAccountRepository $bankAccountRepository;
    private EntityManagerInterface $em;
    private BankAccountService $bankAccountService;

    protected function setUp(): void
    {
        $this->bankAccountRepository = $this->createStub(
            BankAccountRepository::class
        );

        $this->em = $this->createStub(
            EntityManagerInterface::class
        );

        $this->bankAccountService = new BankAccountService(
            $this->bankAccountRepository,
            $this->em
        );
    }

    public function testCreateBankAccountMasksAccountNumber(): void
    {
        $dossier = new Dossier();

        $data = [
            'account_type' => 'current_account',
            'account_number' => '123 456 789 237',
        ];

        $bankAccount = $this->bankAccountService->create($dossier, $data);

        $this->assertInstanceOf(BankAccount::class, $bankAccount);
        $this->assertSame(
            'xxxxxxxxx237',
            $bankAccount->getAccountNumberMasked()
        );
    }
}