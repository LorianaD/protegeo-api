<?php

namespace App\Tests\Service\ManagementAccount;

use App\Entity\Dossier;
use App\Entity\ManagementAccount;
use App\Enum\ManagementAccountStatus;
use App\Repository\ManagementAccountRepository;
use App\Service\ManagementAccount\ManagementAccountService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class ManagementAccountServiceTest extends TestCase
{
    private ManagementAccountRepository&Stub $managementAccountRepository;
    private EntityManagerInterface&Stub $em;
    private ManagementAccountService $managementAccountService;

    protected function setUp(): void
    {
        $this->managementAccountRepository = $this->createStub(
            ManagementAccountRepository::class
        );

        $this->em = $this->createStub(
            EntityManagerInterface::class
        );

        $this->managementAccountService = new ManagementAccountService(
            $this->managementAccountRepository,
            $this->em
        );
    }

    public function testCreateInitialCreatesManagementAccountInProgress(): void
    {
        $dossier = new Dossier();
        $dossier->setOpenedAt(new \DateTimeImmutable('2026-09-09'));

        $managementAccount = $this->managementAccountService->createInitial($dossier);

        $this->assertInstanceOf(ManagementAccount::class, $managementAccount);
        $this->assertSame($dossier, $managementAccount->getDossier());
        $this->assertSame('2026', $managementAccount->getYear()?->format('Y'));
        $this->assertSame(
            ManagementAccountStatus::IN_PROGRESS,
            $managementAccount->getStatus()
        );
    }

    public function testApplyStatusSetsSentDateWhenStatusIsSent(): void
    {
        $managementAccount = new ManagementAccount();
        $sentAt = new \DateTimeImmutable('2026-09-09 10:00:00');

        $this->managementAccountService->applyStatus(
            $managementAccount,
            ManagementAccountStatus::SENT,
            $sentAt
        );

        $this->assertSame(
            ManagementAccountStatus::SENT,
            $managementAccount->getStatus()
        );

        $this->assertSame(
            $sentAt,
            $managementAccount->getSentAt()
        );
    }

    public function testApplyStatusCreatesSentDateWhenNoneIsProvided(): void
    {
        $managementAccount = new ManagementAccount();

        $this->managementAccountService->applyStatus(
            $managementAccount,
            ManagementAccountStatus::SENT
        );

        $this->assertSame(
            ManagementAccountStatus::SENT,
            $managementAccount->getStatus()
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $managementAccount->getSentAt()
        );
    }

    public function testApplyStatusClearsSentDateWhenStatusIsNotSent(): void
    {
        $managementAccount = new ManagementAccount();

        $managementAccount->setSentAt(
            new \DateTimeImmutable('2026-09-09 10:00:00')
        );

        $this->managementAccountService->applyStatus(
            $managementAccount,
            ManagementAccountStatus::VALIDATED
        );

        $this->assertSame(
            ManagementAccountStatus::VALIDATED,
            $managementAccount->getStatus()
        );

        $this->assertNull(
            $managementAccount->getSentAt()
        );
    }

    public function testApplyStatusFailsWhenStatusIsInvalid(): void
    {
        $managementAccount = new ManagementAccount();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut est invalide.');

        $this->managementAccountService->applyStatus(
            $managementAccount,
            'invalid_status'
        );
    }
}