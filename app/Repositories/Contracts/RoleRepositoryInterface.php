<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Role repository interface
 */
interface RoleRepositoryInterface
{
    /**
     * Find a role by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Role;

    /**
     * Find a role by name
     */
    public function findByName(string $name): ?Role;

    /**
     * Get all roles with permissions
     *
     * @return Collection<int, Role>
     */
    public function getAllWithPermissions(): Collection;
}
