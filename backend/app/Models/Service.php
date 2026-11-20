<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name', 'platform', 'description', 'price_per_1000',
        'minimum_quantity', 'maximum_quantity', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_1000' => 'decimal:2',
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
