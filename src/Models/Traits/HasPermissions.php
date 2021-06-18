<?php


namespace Firebed\Permission\Models\Traits;


use Firebed\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Trait HasPermissions
 * @package App\Models\Gate\Traits
 *
 * @property Collection permissions
 */
trait HasPermissions
{
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function getPermissionsAttribute(): Collection
    {
        if ($this->relationLoaded('permissions')) {
            return $this->getRelationValue('permissions');
        }

        $permissions = Cache::remember($this->getPermissionsCacheKey(), config('permission.ttl'), function () {
            return $this->getRelationValue('permissions');
        });

        $this->setRelation('permissions', $permissions);

        return $permissions;
    }

    public function checkPermissionTo($permission): bool
    {
        return $this->permissions->contains('name', $permission);
    }

    public function syncPermissions($permissions): void
    {
        $this->permissions()->sync($permissions);
        $this->forgetCachedPermissions();
    }

    public function givePermissionTo(...$permissions): void
    {
        $permissions = collect($permissions)
            ->map(function ($permission) {
                if (is_numeric($permission)) {
                    return Permission::findOrFail($permission);
                }
                if (is_string($permission)) {
                    return Permission::where('name', $permission)->firstOrFail();
                }
                return $permission;
            })
            ->all();

        $this->permissions()->saveMany($permissions);

        $this->forgetCachedPermissions();
    }

    public function forgetCachedPermissions(): void
    {
        Cache::forget($this->getPermissionsCacheKey());
    }

    private function getPermissionsCacheKey(): string
    {
        return config('permission.permission_key') . $this->id;
    }
}
