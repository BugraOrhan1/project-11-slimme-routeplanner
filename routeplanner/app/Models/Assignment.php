<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Inspectieopdracht: één bezoek aan een tankstation.
 */
class Assignment extends Model
{
    use LogsChanges;

    public const LOG_LABEL = 'Opdracht';

    public const STATUSES = ['open', 'ingepland', 'uitgevoerd', 'geannuleerd'];

    protected $fillable = ['location_id', 'status', 'plan_month', 'deadline', 'priority', 'notes'];

    protected function casts(): array
    {
        return ['deadline' => 'date:Y-m-d', 'plan_month' => 'date:Y-m-d'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }

    public function stop(): HasOne
    {
        return $this->hasOne(RouteStop::class);
    }

    /** Geschatte duur: som van de activiteiten, afhankelijk van het aantal tanks. */
    public function estimatedMinutes(): int
    {
        $tanks = $this->location->tanks_count ?? $this->location->tanks()->count();

        return max(15, (int) $this->activities->sum(fn (Activity $activity) => $activity->minutesFor($tanks)));
    }

    public function requiredCertificateIds(): array
    {
        return $this->activities->pluck('certificate_id')->filter()->unique()->values()->all();
    }

    public function logName(): string
    {
        return $this->location?->name ?? '#'.$this->id;
    }
}
