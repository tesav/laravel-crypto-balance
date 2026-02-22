<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    public const MIN_UNIT = 1;

    protected $fillable = [
        'user_id',
        'currency',
        'balance',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function deposit(int $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function withdrawal(int $amount): void
    {
        $this->decrement('balance', $amount);
    }
}
