<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tankstation van een klant.
 */
class Location extends Model
{
    use LogsChanges;

    public const LOG_LABEL = 'Locatie';

    protected $fillable = [
        'customer_id', 'name', 'address', 'postal_code', 'city', 'region',
        'latitude', 'longitude', 'open_from', 'open_until', 'access_notes',
    ];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
