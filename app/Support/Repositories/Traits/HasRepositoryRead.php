<?php

namespace Support\Repositories\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 * @require-extends \Support\Repositories\Repository<TModel>
 */
trait HasRepositoryRead
{
    /**
     * @param string|int $id
     * @return TModel|null
     */
    public function find(string|int $id): ?Model
    {
        $callback = fn() => $this->query()->find($id);

        if (method_exists($this, 'remember')) {
            return $this->remember("id:{$id}", $callback);
        }

        return $callback();
    }

    /**
     * @param array<string|int> $ids
     * @return Collection<int, TModel>
     */
    public function findMany(array $ids): Collection
    {
        return $this->query()->whereIn('id', $ids)->get();
    }

    /**
     * @return Collection<int, TModel>
     */
    public function all(): Collection
    {
        $callback = fn() => $this->query()->get();

        if (method_exists($this, 'remember')) {
            return $this->remember("all", $callback, 300);
        }

        return $callback();
    }
}
