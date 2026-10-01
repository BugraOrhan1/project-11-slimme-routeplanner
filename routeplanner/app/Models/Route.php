<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dagroute van één inspecteur. Status "voorstel" tot de planner hem goedkeurt.
 */
class Route extends Model
{
    use LogsChanges;

    public const LOG_LABEL = 'Route';

    protected $fillable = [
        'inspector_id', 'date', 'status', 'total_km', 'total_drive_minutes', 'total_work_minutes',
        'end_time', 'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'approved_at' => 'datetime', 'total_km' => 'float'];
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('position');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logName(): string
    {
        return ($this->inspector?->name ?? 'inspecteur').' op '.$this->date?->format('d-m-Y');
    }
}
