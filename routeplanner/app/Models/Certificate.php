<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certificate extends Model
{
    use LogsChanges;

    public const LOG_LABEL = 'Certificaat';

    protected $fillable = ['name', 'description'];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function inspectors(): BelongsToMany
    {
        return $this->belongsToMany(Inspector::class)->withPivot('valid_until');
    }
}
