<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Media;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of MediaRepositoryInterface
 *
 * @extends BaseEloquentRepository<Media>
 */
final class EloquentMediaRepository extends BaseEloquentRepository implements MediaRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<Media>
     */
    protected function getModelClass(): string
    {
        return Media::class;
    }

    /**
     * Create a new media record
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Media
    {
        /** @var Media $media */
        $media = parent::create($data);

        return $media;
    }

    /**
     * Find a media by ID
     */
    public function findById(int $id): ?Media
    {
        /** @var Media|null $media */
        $media = parent::findById($id);

        return $media;
    }

    /**
     * Find a media by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Media
    {
        /** @var Media $media */
        $media = parent::findOrFail($id);

        return $media;
    }

    /**
     * Delete a media record
     */
    public function delete(int $id): bool
    {
        return parent::delete($id);
    }

    /**
     * Get paginated media
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Media>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        return parent::paginate($params);
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Media>
     */
    public function query(): Builder
    {
        /** @var Builder<Media> $builder */
        $builder = parent::query();

        return $builder;
    }
}
