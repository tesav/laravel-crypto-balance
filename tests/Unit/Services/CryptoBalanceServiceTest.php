<?php

namespace Tests\Unit\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Wallet;
use App\Services\CryptoBalanceService;
use App\Services\RiskAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CryptoBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CryptoBalanceService $service;
    protected $riskServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->riskServiceMock = Mockery::mock(RiskAssessmentService::class);
        $this->service = new CryptoBalanceService($this->riskServiceMock);
    }

    public function testDepositCreatesTransaction(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 1000]);
        $amount = 500;
        $feePercent = 2;

        $this->riskServiceMock->shouldReceive('validateDeposit')
            ->once()
            ->withArgs(function ($amt) use ($amount, $feePercent) {
                $expectedTotal = (int) round($amount - ($amount * ($feePercent / 100)));
                return $amt === $expectedTotal;
            });

        $transaction = $this->service->deposit($wallet, $amount, $feePercent);
        $transaction->refresh();

        $expectedFee = (int) round($amount * ($feePercent / 100));
        $expectedAmount = $amount - $expectedFee;

        $this->assertEquals($expectedFee, $transaction->fee);
        $this->assertEquals($expectedAmount, $transaction->amount);
        $this->assertEquals(TransactionStatus::Pending, $transaction->status);
    }

    public function testWithdrawalCreatesTransactionAndDeductsAmount(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 1000]);
        $amount = 200;
        $feePercent = 5;

        $this->riskServiceMock->shouldReceive('validateWithdrawal')
            ->once()
            ->withArgs(function ($w, $total) use ($amount, $feePercent) {
                $expectedTotal = (int) round($amount * (1 + $feePercent / 100));
                return $w instanceof Wallet && $total === $expectedTotal;
            });

        $transaction = $this->service->withdrawal($wallet, $amount, $feePercent);
        $transaction->refresh();
        $wallet->refresh();

        $expectedFee = (int) round($amount * ($feePercent / 100));
        $expectedTotal = $amount + $expectedFee;

        $this->assertEquals($expectedFee, $transaction->fee);
        $this->assertEquals($expectedTotal, $transaction->amount);
        $this->assertEquals(1000 - $expectedTotal, $wallet->balance);
        $this->assertEquals(TransactionStatus::Pending, $transaction->status);
    }

    public function testConfirmDepositUpdatesWalletAndTransaction(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 0]);
        $amount = 500;
        $feePercent = 2;

        $this->riskServiceMock->shouldReceive('validateDeposit')
            ->once()
            ->withArgs(function ($amt) use ($amount, $feePercent) {
                $expectedTotal = (int) round($amount - ($amount * ($feePercent / 100)));
                return $amt === $expectedTotal;
            });

        $transaction = $this->service->deposit($wallet, $amount, $feePercent);

        $this->riskServiceMock->shouldReceive('validateCompleted')
            ->once()
            ->with($transaction);

        $this->service->confirm($transaction);

        $transaction->refresh();
        $wallet->refresh();

        $expectedFee = (int) round($amount * ($feePercent / 100));
        $expectedAmount = $amount - $expectedFee;

        $this->assertEquals(TransactionStatus::Confirmed, $transaction->status);
        $this->assertEquals($expectedAmount, $wallet->balance);
    }

    public function testCancelWithdrawalRefundsWalletAndMarksFailed(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 500]);
        $amount = 200;
        $feePercent = 5;

        $this->riskServiceMock->shouldReceive('validateWithdrawal')
            ->once()
            ->withArgs(function ($w, $total) use ($amount, $feePercent) {
                $expectedTotal = (int) round($amount * (1 + $feePercent / 100));
                return $w instanceof Wallet && $total === $expectedTotal;
            });

        $transaction = $this->service->withdrawal($wallet, $amount, $feePercent);

        $this->riskServiceMock->shouldReceive('validateCompleted')
            ->once()
            ->with($transaction);

        $this->service->cancel($transaction);

        $transaction->refresh();
        $wallet->refresh();

        $expectedFee = (int) round($amount * ($feePercent / 100));
        $expectedTotal = $amount + $expectedFee;

        $this->assertEquals(TransactionStatus::Failed, $transaction->status);
        $this->assertEquals(500, $wallet->balance);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
