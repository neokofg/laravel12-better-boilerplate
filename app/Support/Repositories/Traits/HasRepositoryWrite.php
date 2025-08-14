<?php

namespace Support\Repositories\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 * @require-extends \Support\Repositories\Repository<TModel>
 */
trait HasRepositoryWrite
{
    /**
     * @param array $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        $model = $this->query()->create($data);

        if (method_exists($this, 'invalidateFor')) {
            $this->invalidateFor($model);
        }

        return $model;
    }

    /**
     * @param TModel $model
     * @param array $data
     * @return bool
     */
    public function update(Model $model, array $data): bool
    {
        $result = $model->update($data);

        if (method_exists($this, 'invalidateFor')) {
            $this->invalidateFor($model);
        }

        return $result;
    }

    /**
     * @param TModel $model
     * @return bool
     */
    public function delete(Model $model): bool
    {
        if (method_exists($this, 'invalidateFor')) {
            $this->invalidateFor($model);
        }

        return $model->delete();
    }

    /**
     * @param array<string|int> $ids
     * @param array $data
     * @return int
     */
    public function updateMany(array $ids, array $data): int
    {
        if (method_exists($this, 'forgetByPattern')) {
            $this->forgetByPattern('*');
        }

        return $this->query()->whereIn('id', $ids)->update($data);
    }

    /**
     * @param array<string|int> $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        if (method_exists($this, 'forget')) {
            foreach ($ids as $id) {
                $this->forget("id:{$id}");
            }
        }

        return $this->query()->whereIn('id', $ids)->delete();
    }
}
