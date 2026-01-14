<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * Permission repository interface
 */
interface PermissionRepositoryInterface
{
    /**
     * Get all permissions
     *
     * @return Collection<int, Permission>
     */
    public function getAll(): Collection;
}
