<?php

namespace Support\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Model
 */
abstract class Repository
{
    /** @var TModel */
    protected Model $model;

    /**
     * @param TModel $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return Builder<TModel>|TModel
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * @return class-string<TModel>
     */
    protected function getModelClass(): string
    {
        return get_class($this->model);
    }
}
