<?php

namespace App\Tests\Service\ManagementAccount;

use App\Entity\Dossier;
use App\Entity\ManagementAccount;
use App\Entity\MeasureProtection;
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

        $measureProtection = new MeasureProtection();
        $measureProtection->setStartDate(
            new \DateTimeImmutable('2026-09-09')
        );

        $this->managementAccountRepository
            ->method('findOverlappingPeriod')
            ->willReturn(null);

        $managementAccount = $this
            ->managementAccountService
            ->createInitial(
                $dossier,
                $measureProtection
            );

        $this->assertInstanceOf(
            ManagementAccount::class,
            $managementAccount
        );

        $this->assertSame(
            $dossier,
            $managementAccount->getDossier()
        );

        $this->assertSame(
            '2026',
            $managementAccount->getYear()?->format('Y')
        );

        $this->assertSame(
            '2026-09-09',
            $managementAccount->getStartDate()?->format('Y-m-d')
        );

        $this->assertSame(
            '2027-09-09',
            $managementAccount->getEndDate()?->format('Y-m-d')
        );

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

    public function testValidatePeriodAcceptsAvailablePeriod(): void
    {
        $dossier = new Dossier();
        $startDate = new \DateTimeImmutable('2026-09-09');
        $endDate = new \DateTimeImmutable('2027-09-09');

        $this->managementAccountRepository
            ->method('findOverlappingPeriod')
            ->willReturn(null);

        $this->expectNotToPerformAssertions();

        $this->managementAccountService->validatePeriod(
            $dossier,
            $startDate,
            $endDate
        );
    }

    public function testValidatePeriodRejectsEndDateBeforeStartDate(): void
    {
        $dossier = new Dossier();
        $startDate = new \DateTimeImmutable('2026-09-09');
        $endDate = new \DateTimeImmutable('2026-09-08');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La date de fin doit être postérieure à la date de début.'
        );

        $this->managementAccountService->validatePeriod(
            $dossier,
            $startDate,
            $endDate
        );
    }

    public function testValidatePeriodRejectsOverlappingPeriod(): void
    {
        $dossier = new Dossier();
        $startDate = new \DateTimeImmutable('2026-09-09');
        $endDate = new \DateTimeImmutable('2027-09-09');

        $this->managementAccountRepository
            ->method('findOverlappingPeriod')
            ->willReturn(new ManagementAccount());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Un compte de gestion existe déjà pour tout ou partie de cette période.'
        );

        $this->managementAccountService->validatePeriod(
            $dossier,
            $startDate,
            $endDate
        );
    }
}