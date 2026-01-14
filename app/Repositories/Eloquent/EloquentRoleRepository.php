<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of RoleRepositoryInterface
 *
 * @extends BaseEloquentRepository<Role>
 */
final class EloquentRoleRepository extends BaseEloquentRepository implements RoleRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<Role>
     */
    protected function getModelClass(): string
    {
        return Role::class;
    }

    /**
     * Find a role by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Role
    {
        /** @var Role $role */
        $role = parent::findOrFail($id);

        return $role;
    }

    /**
     * Find a role by name
     */
    public function findByName(string $name): ?Role
    {
        /** @var Role|null $role */
        $role = $this->query()->where('name', $name)->first();

        return $role;
    }

    /**
     * Get all roles with permissions
     *
     * @return Collection<int, Role>
     */
    public function getAllWithPermissions(): Collection
    {
        /** @var Collection<int, Role> $roles */
        $roles = $this->query()->with(['permissions:id,name,slug'])->get();

        return $roles;
    }
}
