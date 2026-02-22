<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Failed = 'failed';

    public function isCompleted(): bool
    {
        return $this !== TransactionStatus::Pending;

        // return match ($this) {
        //     self::Confirmed, self::Failed => true,
        //     default => false,
        // };
    }
}
