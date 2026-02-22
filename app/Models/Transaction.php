<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'uuid',
        'wallet_id',
        'type',
        'amount',
        'fee',
        'status',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid(); // Generate UUID for each transaction
        });
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Wallet::class, 'id', 'id', 'wallet_id', 'user_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
