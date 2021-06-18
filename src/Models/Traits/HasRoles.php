<?php


namespace Firebed\Permission\Models\Traits;


use Firebed\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Trait HasRoles
 * @package App\Models\Gate\Traits
 *
 * @property Collection roles
 *
 * @method Builder role($roles)
 */
trait HasRoles
{
    use HasPermissions;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function getRolesAttribute(): Collection
    {
        if ($this->relationLoaded('roles')) {
            return $this->getRelationValue('roles');
        }

        $roles = Cache::remember($this->getRolesCacheKey(), config('permission.ttl'), function () {
            return $this->getRelationValue('roles');
        });

        $this->setRelation('roles', $roles);

        return $roles;
    }

    public function hasRole($roles): bool
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->roles->contains('name', $role)) {
                    return true;
                }
            }
        }
        return $this->roles->contains('name', $roles);
    }

    public function hasAnyRole($roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasAllRoles($roles): bool
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (!$this->roles->contains('name', $role)) {
                    return false;
                }
            }
            return true;
        }
        return $this->roles->contains('name', $roles);
    }

    public function assignRole(...$roles): void
    {
        $this->roles()->saveMany($roles);
        $this->forgetCachedRoles();
    }

    public function syncRoles($roles): void
    {
        $this->roles()->sync($roles);
        $this->forgetCachedRoles();
    }

    public function scopeRole(Builder $builder, $roles): Builder
    {
        if ($roles instanceof Collection) {
            $roles = $roles->all();
        }

        if (! is_array($roles)) {
            $roles = [$roles];
        }

        return $builder->whereHas('roles', fn($q) => $q->whereIn('name', $roles));
    }

    public function forgetCachedRoles(): void
    {
        Cache::forget($this->getRolesCacheKey());
    }

    private function getRolesCacheKey(): string
    {
        return config('permission.role_key') . $this->id;
    }
}
