<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Bank;
use App\Models\Cheque;
use App\Models\BankTransaction;
use App\Models\User;
use App\Models\Branch;
use App\Services\Banking\BankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BankingService $service;
    protected Branch $branch;
    protected User $user;
    protected BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BankingService::class);
        $this->branch = Branch::factory()->create();
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        $bank = Bank::factory()->create();
        $this->bankAccount = BankAccount::factory()->create(['bank_id' => $bank->id]);

        $this->actingAs($this->user);
    }

    public function test_record_deposit()
    {
        $initialBalance = $this->bankAccount->current_balance;
        $depositAmount = '50000.00';

        $transaction = $this->service->recordDeposit(
            $this->bankAccount->id,
            'SHIFT-2026-000001',
            $depositAmount,
            'Cash deposit from shift'
        );

        $this->assertNotNull($transaction);
        $this->assertEquals(BankTransaction::TYPE_DEPOSIT, $transaction->type);
        $this->assertEquals($depositAmount, $transaction->credit_amount);

        $this->bankAccount->refresh();
        $expectedBalance = bcadd($initialBalance, $depositAmount, 2);
        $this->assertEquals($expectedBalance, $this->bankAccount->current_balance);
    }

    public function test_issue_cheque()
    {
        $cheque = $this->service->issueCheque(
            $this->bankAccount->id,
            '123456',
            'Test Supplier',
            '100000.00',
            new \DateTime('2026-10-01'),
            new \DateTime('2026-10-15')
        );

        $this->assertNotNull($cheque);
        $this->assertEquals(Cheque::STATUS_ISSUED, $cheque->status);
        $this->assertEquals('100000.00', $cheque->amount);
        $this->assertEquals('123456', $cheque->cheque_number);
    }

    public function test_clear_cheque()
    {
        $cheque = Cheque::factory()->create(['bank_account_id' => $this->bankAccount->id]);
        $initialBalance = $this->bankAccount->current_balance;

        $this->service->clearCheque($cheque, $this->bankAccount->id);

        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_CLEARED, $cheque->status);

        $this->bankAccount->refresh();
        $expectedBalance = bcsub($initialBalance, $cheque->amount, 2);
        $this->assertEquals($expectedBalance, $this->bankAccount->current_balance);
    }

    public function test_bounce_cheque()
    {
        $cheque = Cheque::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'status' => Cheque::STATUS_PRESENTED,
        ]);
        $initialBalance = $this->bankAccount->current_balance;

        $this->service->bounceCheque($cheque, $this->bankAccount->id, 'Insufficient funds');

        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_BOUNCED, $cheque->status);
        $this->assertStringContainsString('Insufficient funds', $cheque->notes);

        $this->bankAccount->refresh();
        $expectedBalance = bcadd($initialBalance, $cheque->amount, 2);
        $this->assertEquals($expectedBalance, $this->bankAccount->current_balance);
    }

    public function test_compute_book_balance()
    {
        BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_DEPOSIT,
            'credit_amount' => '50000.00',
            'debit_amount' => '0.00',
        ]);

        $bookBalance = $this->service->computeBookBalance($this->bankAccount->id, now());

        $expected = bcadd($this->bankAccount->opening_balance, '50000.00', 2);
        $this->assertEquals($expected, $bookBalance);
    }

    public function test_get_reconciliation_report()
    {
        // Record some transactions
        BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_DEPOSIT,
            'credit_amount' => '100000.00',
            'debit_amount' => '0.00',
            'reconciled' => true,
        ]);

        BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_WITHDRAWAL,
            'credit_amount' => '0.00',
            'debit_amount' => '25000.00',
            'reconciled' => false,
        ]);

        $report = $this->service->getReconciliationReport($this->bankAccount->id, now());

        $this->assertArrayHasKey('as_of_date', $report);
        $this->assertArrayHasKey('book_balance', $report);
        $this->assertArrayHasKey('unreconciled_count', $report);
        $this->assertEquals(1, $report['unreconciled_count']);
    }

    public function test_reconcile_statement()
    {
        $transaction = BankTransaction::factory()->create([
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_DEPOSIT,
            'credit_amount' => '50000.00',
            'debit_amount' => '0.00',
            'reconciled' => false,
        ]);

        $statementBalance = '500000.00';
        $reconciliation = $this->service->reconcileStatement(
            $this->bankAccount->id,
            new \DateTime(),
            $statementBalance,
            [$transaction->id]
        );

        $this->assertNotNull($reconciliation);
        $transaction->refresh();
        $this->assertTrue($transaction->reconciled);
    }
}
