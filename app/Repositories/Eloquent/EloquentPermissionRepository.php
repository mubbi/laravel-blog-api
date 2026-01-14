<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Permission;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of PermissionRepositoryInterface
 *
 * @extends BaseEloquentRepository<Permission>
 */
final class EloquentPermissionRepository extends BaseEloquentRepository implements PermissionRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<Permission>
     */
    protected function getModelClass(): string
    {
        return Permission::class;
    }

    /**
     * Get all permissions
     *
     * @return Collection<int, Permission>
     */
    public function getAll(): Collection
    {
        /** @var Collection<int, Permission> $permissions */
        $permissions = $this->query()->get();

        return $permissions;
    }
}
