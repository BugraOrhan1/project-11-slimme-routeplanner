<?php

namespace App\Models\Concerns;

use App\Models\ChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Legt vast wie wat heeft aangepast (wens van Marijn, gesprek 23-09).
 */
trait LogsChanges
{
    public static function bootLogsChanges(): void
    {
        static::created(fn (Model $model) => $model->writeChangeLog('aangemaakt'));

        static::updated(function (Model $model) {
            $changes = collect($model->getChanges())->except(['updated_at'])->all();
            if ($changes === []) {
                return;
            }
            $old = collect($changes)->mapWithKeys(fn ($value, $key) => [$key => $model->getOriginal($key)])->all();
            $model->writeChangeLog('gewijzigd', ['oud' => $old, 'nieuw' => $changes]);
        });

        static::deleted(fn (Model $model) => $model->writeChangeLog('verwijderd'));
    }

    public function writeChangeLog(string $action, ?array $changes = null, ?string $description = null): void
    {
        if (! Auth::check()) {
            return; // seeders en console-taken worden niet gelogd
        }

        ChangeLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => static::LOG_LABEL,
            'subject_id' => $this->getKey(),
            'description' => $description ?? static::LOG_LABEL.' "'.$this->logName().'" '.$action,
            'changes' => $changes,
        ]);
    }

    public function logName(): string
    {
        return (string) ($this->name ?? '#'.$this->getKey());
    }
}
