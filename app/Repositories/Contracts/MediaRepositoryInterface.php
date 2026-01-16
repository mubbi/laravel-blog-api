<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Media repository interface
 */
interface MediaRepositoryInterface
{
    /**
     * Create a new media record
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Media;

    /**
     * Update an existing media record
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a media by ID
     */
    public function findById(int $id): ?Media;

    /**
     * Find a media by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Media;

    /**
     * Delete a media record
     */
    public function delete(int $id): bool;

    /**
     * Get all media
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, Media>
     */
    public function all(?array $columns = null): Collection;

    /**
     * Get paginated media
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Media>
     */
    public function paginate(array $params): LengthAwarePaginator;

    /**
     * Get a query builder instance
     *
     * @return Builder<Media>
     */
    public function query(): Builder;
}
