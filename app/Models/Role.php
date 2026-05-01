<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get all users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Assign permissions to the role.
     */
    public function assignPermissions(array $permissions): void
    {
        $this->permissions = array_unique(array_merge($this->permissions ?? [], $permissions));
        $this->save();
    }

    /**
     * Revoke permissions from the role.
     */
    public function revokePermissions(array $permissions): void
    {
        $this->permissions = array_diff($this->permissions ?? [], $permissions);
        $this->save();
    }
}
