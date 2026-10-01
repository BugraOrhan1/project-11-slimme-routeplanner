<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    use LogsChanges;

    public const LOG_LABEL = 'Klant';

    protected $fillable = ['name', 'contact_person', 'email', 'phone', 'color'];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(Assignment::class, Location::class);
    }
}
