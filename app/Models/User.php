<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
