<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tank extends Model
{
    public const PRODUCTS = ['Diesel', 'Euro 95', 'Euro 98', 'AdBlue', 'LPG', 'HVO'];

    protected $fillable = ['location_id', 'product', 'kind', 'capacity_liters', 'last_inspection'];

    protected function casts(): array
    {
        return ['last_inspection' => 'date:Y-m-d'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
