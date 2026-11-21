<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name', 'platform', 'category', 'provider', 'provider_service_id',
        'provider_type', 'provider_rate', 'provider_currency', 'provider_refill',
        'provider_cancel', 'description', 'price_per_1000',
        'minimum_quantity', 'maximum_quantity', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_1000' => 'decimal:2',
            'provider_rate' => 'decimal:6',
            'provider_refill' => 'boolean',
            'provider_cancel' => 'boolean',
            'minimum_quantity' => 'integer',
            'maximum_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
