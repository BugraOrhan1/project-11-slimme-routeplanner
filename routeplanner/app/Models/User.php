<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Rollen uit het gesprek van 23-09 (plus beheerder). Klanten krijgen geen account. */
    public const ROLES = [
        'planner' => 'Planner',
        'bedrijfsleider' => 'Bedrijfsleider',
        'technisch_manager' => 'Technisch manager',
        'administratie' => 'Administratie',
        'inspecteur' => 'Inspecteur',
        'beheerder' => 'Beheerder',
    ];

    /** Deze rollen krijgen een melding als een deadline bijna verloopt. */
    public const ALERT_ROLES = ['planner', 'bedrijfsleider'];

    /** Deze rollen mogen gebruikers beheren. */
    public const ADMIN_ROLES = ['beheerder', 'bedrijfsleider'];

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function inspector(): HasOne
    {
        return $this->hasOne(Inspector::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function isInspector(): bool
    {
        return $this->role === 'inspecteur';
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES, true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
