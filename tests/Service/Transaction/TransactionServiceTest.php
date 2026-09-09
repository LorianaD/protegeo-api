<?php

namespace App\Tests\Service\Transaction;

use App\Entity\BankAccount;
use App\Entity\ManagementAccount;
use App\Entity\Transaction;
use App\Enum\PaymentMethod;
use App\Enum\TransactionCategoryGroup;
use App\Enum\TransactionCategoryType;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use App\Service\Transaction\TransactionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class TransactionServiceTest extends TestCase
{
    private TransactionRepository&Stub $transactionRepository;
    private EntityManagerInterface&Stub $em;
    private TransactionService $transactionService;

    protected function setUp(): void
    {
        $this->transactionRepository = $this->createStub(
            TransactionRepository::class
        );

        $this->em = $this->createStub(
            EntityManagerInterface::class
        );

        $this->transactionService = new TransactionService(
            $this->transactionRepository,
            $this->em
        );
    }

    public function testCreateTransactionSuccessfully(): void
    {
        $managementAccount = new ManagementAccount();
        $bankAccount = new BankAccount();

        $transaction = $this->transactionService->create(
            $managementAccount,
            $bankAccount,
            [
                'transaction_type' => TransactionType::EXPENSE,
                'category_group' => TransactionCategoryGroup::HOUSING,
                'category_type' => TransactionCategoryType::RENT,
                'label' => 'Loyer',
                'amount' => 125.50,
                'operation_date' => '2026-09-09',
                'payment_method' => PaymentMethod::BANK_CARD,
            ]
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame($managementAccount, $transaction->getAccount());
        $this->assertSame($bankAccount, $transaction->getBankAccount());
        $this->assertSame(TransactionType::EXPENSE, $transaction->getTransactionType());
        $this->assertSame(TransactionCategoryGroup::HOUSING, $transaction->getCategoryGroup());
        $this->assertSame(TransactionCategoryType::RENT, $transaction->getCategoryType());
        $this->assertSame('Loyer', $transaction->getLabel());
        $this->assertSame('125.500', $transaction->getAmount());
        $this->assertSame('2026-09-09', $transaction->getOperationDate()->format('Y-m-d'));
        $this->assertSame(PaymentMethod::BANK_CARD, $transaction->getPaymentMethod());
    }

    public function testCreateTransactionFailsWhenTransactionTypeIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le type de transaction est obligatoire.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            []
        );
    }

    public function testCreateTransactionFailsWhenCategoryGroupIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le groupe de catégorie est obligatoire.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => TransactionType::EXPENSE,
            ]
        );
    }

    public function testCreateTransactionFailsWhenCategoryTypeIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La catégorie est obligatoire.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => TransactionType::EXPENSE,
                'category_group' => TransactionCategoryGroup::HOUSING,
            ]
        );
    }

    public function testCreateTransactionFailsWhenAmountIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le montant doit être un nombre valide.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => TransactionType::EXPENSE,
                'category_group' => TransactionCategoryGroup::HOUSING,
                'category_type' => TransactionCategoryType::RENT,
                'amount' => 'abc',
                'operation_date' => '2026-09-09',
            ]
        );
    }

    public function testCreateTransactionFailsWhenAmountIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le montant doit être supérieur à zéro.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => TransactionType::EXPENSE,
                'category_group' => TransactionCategoryGroup::HOUSING,
                'category_type' => TransactionCategoryType::RENT,
                'amount' => -10,
                'operation_date' => '2026-09-09',
            ]
        );
    }

    public function testCreateTransactionFailsWhenPaymentMethodIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le moyen de paiement est invalide.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => TransactionType::EXPENSE,
                'category_group' => TransactionCategoryGroup::HOUSING,
                'category_type' => TransactionCategoryType::RENT,
                'amount' => 100,
                'operation_date' => '2026-09-09',
                'payment_method' => 'invalid_payment_method',
            ]
        );
    }

    public function testCreateTransactionFailsWhenTransactionTypeIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le type de transaction est invalide.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => 'invalid_transaction_type',
                'category_group' => TransactionCategoryGroup::HOUSING,
                'category_type' => TransactionCategoryType::RENT,
                'amount' => 100,
                'operation_date' => '2026-09-09',
            ]
        );
    }

    public function testCreateTransactionFailsWhenCategoryGroupIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le groupe de catégorie est invalide.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => TransactionType::EXPENSE,
                'category_group' => 'invalid_category_group',
                'category_type' => TransactionCategoryType::RENT,
                'amount' => 100,
                'operation_date' => '2026-09-09',
            ]
        );
    }

    public function testCreateTransactionFailsWhenCategoryTypeIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'La catégorie est invalide.'
        );

        $this->transactionService->create(
            new ManagementAccount(),
            null,
            [
                'transaction_type' => TransactionType::EXPENSE,
                'category_group' => TransactionCategoryGroup::HOUSING,
                'category_type' => 'invalid_category_type',
                'amount' => 100,
                'operation_date' => '2026-09-09',
            ]
        );
    }

    public function testGetByManagementAccountAndMonthFailsWhenMonthIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le mois doit être compris entre 1 et 12.'
        );

        $this->transactionService->getByManagementAccountAndMonth(
            new ManagementAccount(),
            2026,
            13
        );
    }

    public function testUpdateTransactionChangesBankAccount(): void
    {
        $transaction = new Transaction();
        $bankAccount = new BankAccount();

        $updatedTransaction = $this->transactionService->update(
            $transaction,
            [
                'bank_account_id' => 1,
            ],
            $bankAccount
        );

        $this->assertSame(
            $bankAccount,
            $updatedTransaction->getBankAccount()
        );

        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $updatedTransaction->getUpdatedAt()
        );
    }

    public function testUpdateTransactionCanRemoveBankAccount(): void
    {
        $transaction = new Transaction();
        $transaction->setBankAccount(new BankAccount());

        $updatedTransaction = $this->transactionService->update(
            $transaction,
            [
                'bank_account_id' => null,
            ],
            null
        );

        $this->assertNull(
            $updatedTransaction->getBankAccount()
        );
    }
}