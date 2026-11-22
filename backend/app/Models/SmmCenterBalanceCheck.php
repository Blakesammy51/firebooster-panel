<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmmCenterBalanceCheck extends Model
{
    protected $fillable = ['amount', 'currency', 'status', 'error_message', 'checked_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:8', 'checked_at' => 'datetime'];
    }
}
