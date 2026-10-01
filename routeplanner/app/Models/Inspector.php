<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Inspector extends Model
{
    use LogsChanges;

    public const LOG_LABEL = 'Inspecteur';

    protected $fillable = [
        'user_id', 'name', 'phone', 'start_address', 'start_latitude', 'start_longitude',
        'work_start', 'work_end', 'color', 'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'start_latitude' => 'float', 'start_longitude' => 'float'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certificates(): BelongsToMany
    {
        return $this->belongsToMany(Certificate::class)->withPivot('valid_until');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    /** Id's van certificaten die op $date nog geldig zijn. */
    public function validCertificateIds(Carbon|string|null $date = null): array
    {
        $date = Carbon::parse($date ?? now())->toDateString();

        return $this->certificates
            ->filter(fn ($certificate) => $certificate->pivot->valid_until === null || $certificate->pivot->valid_until >= $date)
            ->pluck('id')->all();
    }

    /** Mag deze inspecteur alle activiteiten van de opdracht uitvoeren? */
    public function isQualifiedFor(Assignment $assignment, Carbon|string|null $date = null): bool
    {
        return array_diff($assignment->requiredCertificateIds(), $this->validCertificateIds($date)) === [];
    }
}
