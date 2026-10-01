<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Soort werkzaamheid met een vaste tijd (per inspectie 3-4 activiteiten).
 */
class Activity extends Model
{
    use LogsChanges;

    public const LOG_LABEL = 'Activiteit';

    protected $fillable = ['name', 'duration_minutes', 'per_tank', 'certificate_id'];

    protected function casts(): array
    {
        return ['per_tank' => 'boolean'];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /** Duur op een locatie: vaste tijd, of vaste tijd per tank. */
    public function minutesFor(int $tankCount): int
    {
        return $this->per_tank ? $this->duration_minutes * max(1, $tankCount) : $this->duration_minutes;
    }
}
