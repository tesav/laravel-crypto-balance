<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class CryptoBalanceService
{
    public function __construct(
        protected RiskAssessmentService $riskService
    ) {
    }

    public function deposit(Wallet $wallet, int $amount, float $feePercent): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $feePercent) {
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            $fee = (int) round($amount * ($feePercent / 100));

            $totalAmount = $amount - $fee;

            $this->riskService->validateDeposit($totalAmount);

            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Deposit,
                'amount' => $totalAmount,
                'fee' => $fee,
                'status' => TransactionStatus::Pending,
            ]);

            return $transaction;
        });
    }

    public function withdrawal(Wallet $wallet, int $amount, float $feePercent): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $feePercent) {
            $wallet = Wallet::lockForUpdate()->findOrFail($wallet->id);

            $fee = (int) round($amount * ($feePercent / 100));

            $totalAmount = $amount + $fee;

            $this->riskService->validateWithdrawal($wallet, $totalAmount);

            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Withdrawal,
                'amount' => $totalAmount,
                'fee' => $fee,
                'status' => TransactionStatus::Pending,
            ]);

            $wallet->withdrawal($totalAmount);

            return $transaction;
        });
    }

    public function confirm(Transaction $transaction): void
    {
        $this->riskService->validateCompleted($transaction);

        DB::transaction(function () use ($transaction) {
            $transaction = Transaction::lockForUpdate()->findOrFail($transaction->id);
            $wallet = Wallet::lockForUpdate()->findOrFail($transaction->wallet_id);

            match ($transaction->type) {
                TransactionType::Deposit => $wallet->deposit($transaction->amount),
                TransactionType::Withdrawal => null, // Already withdraw in withdrawal() method
            };

            $wallet->save();

            $transaction->update([
                'status' => TransactionStatus::Confirmed,
            ]);
        });
    }

    public function cancel(Transaction $transaction): void
    {
        $this->riskService->validateCompleted($transaction);

        DB::transaction(function () use ($transaction) {
            $transaction = Transaction::lockForUpdate()->findOrFail($transaction->id);
            $wallet = Wallet::lockForUpdate()->findOrFail($transaction->wallet_id);

            match ($transaction->type) {
                TransactionType::Withdrawal => $wallet->deposit($transaction->amount),
                TransactionType::Deposit => null, // Nothing
            };

            $transaction->update([
                'status' => TransactionStatus::Failed
            ]);
        });
    }
}
