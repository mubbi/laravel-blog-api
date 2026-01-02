<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Eloquent repository with common CRUD operations
 *
 * @template TModel of Model
 */
abstract class BaseEloquentRepository
{
    /**
     * Get the model class name
     *
     * @return class-string<TModel>
     */
    abstract protected function getModelClass(): string;

    /**
     * Create a new model instance
     *
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data)
    {
        /** @var class-string<TModel> $modelClass */
        $modelClass = $this->getModelClass();

        /** @var TModel $model */
        $model = $modelClass::create($data);

        return $model;
    }

    /**
     * Update an existing model
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($data);
    }

    /**
     * Find a model by ID
     *
     * @return TModel|null
     */
    public function findById(int $id)
    {
        /** @var class-string<TModel> $modelClass */
        $modelClass = $this->getModelClass();

        /** @var TModel|null $model */
        $model = $modelClass::find($id);

        return $model;
    }

    /**
     * Find a model by ID or fail
     *
     * @return TModel
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id)
    {
        /** @var class-string<TModel> $modelClass */
        $modelClass = $this->getModelClass();

        /** @var TModel $model */
        $model = $modelClass::findOrFail($id);

        return $model;
    }

    /**
     * Delete a model
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);

        /** @var bool $result */
        $result = $model->delete();

        return $result;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<TModel>
     */
    public function query()
    {
        /** @var class-string<TModel> $modelClass */
        $modelClass = $this->getModelClass();

        /** @var Builder<TModel> $builder */
        $builder = $modelClass::query();

        return $builder;
    }

    /**
     * Get models with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(array $params)
    {
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        /** @var LengthAwarePaginator<int, TModel> $paginator */
        $paginator = $this->query()->paginate((int) $perPage, ['*'], 'page', (int) $page);

        return $paginator;
    }

    /**
     * Get all models
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, TModel>
     */
    public function all(?array $columns = null): Collection
    {
        /** @var class-string<TModel> $modelClass */
        $modelClass = $this->getModelClass();

        if ($columns !== null) {
            /** @var array<int, string> $columnArray */
            $columnArray = array_values($columns);

            /** @var Collection<int, TModel> $collection */
            $collection = $this->query()->get($columnArray);

            return $collection;
        }

        /** @var Collection<int, TModel> $collection */
        $collection = $modelClass::all();

        return $collection;
    }
}
