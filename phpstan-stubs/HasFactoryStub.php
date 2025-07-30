<?php

namespace Illuminate\Database\Eloquent\Factories;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @template-implements \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<TModel>
     */
    public static function factory()
    {
        return new class extends \Illuminate\Database\Eloquent\Factories\Factory
        {
            protected $model = self::class;
        };
    }
}
