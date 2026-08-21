<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'username',
        'must_complete_setup',
        'credentials_updated_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public const ROLES = ['social_officer', 'focal_person', 'admin', 'super_admin'];

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Map legacy stored role labels during the transition to fixed RBAC roles. */
    public function setRoleAttribute(string $value): void
    {
        $this->attributes['role'] = [
            'social_welfare_officer' => 'social_officer',
            'aics_focal_person' => 'focal_person',
            'mswdo_head' => 'admin',
            'technical_expert' => 'super_admin',
        ][$value] ?? $value;
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public static function roleSlotAvailable(string $role, ?int $excludingUserId = null): bool
    {
        if (! in_array($role, ['admin', 'focal_person'], true)) {
            return true;
        }

        return ! static::query()
            ->where('role', $role)
            ->where('status', 'approved')
            ->when($excludingUserId, fn ($query) => $query->whereKeyNot($excludingUserId))
            ->exists();
    }

    /** @return array<int, string> */
    public static function availableLeadershipRoles(): array
    {
        return array_values(array_filter(
            ['admin', 'focal_person'],
            fn (string $role) => static::roleSlotAvailable($role),
        ));
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'MSWDO Head',
            'focal_person' => 'Focal Person',
            'social_officer' => 'Social Worker Officer',
            'super_admin' => 'System Administrator',
            default => str($role)->replace('_', ' ')->title()->toString(),
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'credentials_updated_at' => 'datetime',
            'must_complete_setup' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class, 'encoded_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
