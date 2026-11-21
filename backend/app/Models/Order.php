<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'service_id', 'target_url', 'quantity',
        'unit_price', 'total_price', 'status', 'notes', 'provider_order_id',
        'provider_status', 'provider_charge', 'provider_currency', 'provider_start_count',
        'provider_remains', 'provider_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'provider_charge' => 'decimal:6',
            'provider_start_count' => 'integer',
            'provider_remains' => 'integer',
            'provider_submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
