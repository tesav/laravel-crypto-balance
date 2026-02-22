<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use DomainException;

class RiskAssessmentService
{
    public function validateDeposit(int $amount): void
    {
        $this->validateAmount($amount);
    }

    public function validateWithdrawal(Wallet $wallet, int $amount): void
    {
        $this->validateAmount($amount);

        if ($amount > $wallet->availableBalance()) {
            throw new DomainException('Insufficient funds');
        }
    }

    public function validateCompleted(Transaction $transaction): void
    {
        if ($transaction->status->isCompleted()) {
            throw new DomainException('Transaction already completed');
        }
    }

    private function validateAmount(int $amount): void
    {
        if ($amount < Wallet::MIN_UNIT) {
            throw new DomainException('Amount too low');
        }
    }
}
