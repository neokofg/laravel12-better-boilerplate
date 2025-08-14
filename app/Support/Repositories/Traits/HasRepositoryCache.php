<?php

namespace Support\Repositories\Traits;

use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Log;

/**
 * @require-extends \Support\Repositories\Repository<TModel>
 */
trait HasRepositoryCache
{
    private ?Cache $cache = null;
    protected int $ttl = 3600;

    /**
     * Правила инвалидации: поле => [ключи для очистки]
     * @var array<string, array<string>>
     */
    protected array $cacheInvalidationRules = [
        // 'email' => ['email:*', 'user:email:*'],
        // 'id' => ['id:*', 'user:*'],
    ];

    /**
     * Поля, которые используются как уникальные ключи кеша
     * @var array<string>
     */
    protected array $cacheKeyFields = [
        // 'id', 'email'
    ];

    protected ?string $cachePrefix = null;

    protected function cache(): Cache
    {
        if ($this->cache === null) {
            $manager = app(CacheManager::class);
            $this->cache = $manager->store('redis');
        }
        return $this->cache;
    }

    protected function getCacheKey(string $key): string
    {
        $prefix = $this->cachePrefix ?? strtolower(class_basename($this->getModelClass()));
        return "{$prefix}:{$key}";
    }

    protected function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);

        try {
            return $this->cache()->remember($cacheKey, $ttl ?? $this->ttl, $callback);
        } catch (\Exception $e) {
            Log::warning(__("Cache unavailable for key {$cacheKey}: ") . $e->getMessage());
            return $callback();
        }
    }

    protected function rememberForever(string $key, callable $callback): mixed
    {
        $cacheKey = $this->getCacheKey($key);

        try {
            return $this->cache()->rememberForever($cacheKey, $callback);
        } catch (\Exception $e) {
            Log::warning(__("Cache unavailable for key {$cacheKey}: ") . $e->getMessage());
            return $callback();
        }
    }

    protected function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            try {
                $this->cache()->forget($this->getCacheKey($key));
            } catch (\Exception $e) {
                Log::warning(__("Cannot forget cache key {$key}: ") . $e->getMessage());
            }
        }
    }

    protected function forgetByPattern(string $pattern): void
    {
        $pattern = $this->getCacheKey($pattern);

        try {
            /** @var RedisStore $store */
            $store = $this->cache()->getStore();
            /** @var PhpRedisConnection $redis */
            $redis = $store->connection();

            $cursor = 0;
            do {
                [$cursor, $keys] = $redis->scan($cursor, ['match' => $pattern, 'count' => 100]);

                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } while ($cursor !== '0' && $cursor !== 0);

        } catch (\Exception $e) {
            Log::warning("Cannot forget pattern {$pattern}: " . $e->getMessage());
        }
    }

    protected function flush(): void
    {
        $this->forgetByPattern('*');
    }

    protected function invalidateFor(Model $model): void
    {
        $keys = [];

        foreach ($this->cacheKeyFields as $field) {
            if (isset($model->$field)) {
                $value = $model->$field;
                $keys[] = "{$field}:{$value}";

                if ($model->wasChanged($field)) {
                    $oldValue = $model->getOriginal($field);
                    $keys[] = "{$field}:{$oldValue}";
                }
            }
        }

        if (!empty($keys)) {
            $this->forget(...$keys);
        }

        foreach ($this->cacheInvalidationRules as $field => $patterns) {
            if ($model->wasChanged($field) || !$model->exists) {
                foreach ($patterns as $pattern) {
                    $this->forgetByPattern($pattern);
                }
            }
        }
    }

    protected function invalidateRelated(Model $model, array $relations): void
    {
        foreach ($relations as $relation) {
            if ($model->relationLoaded($relation)) {
                $related = $model->$relation;

                if ($related instanceof Model) {
                    $this->invalidateFor($related);
                } elseif ($related instanceof Collection) {
                    $related->each(fn($item) => $this->invalidateFor($item));
                }
            }
        }
    }

    protected function withCacheInvalidation(Model $model, callable $operation): mixed
    {
        $result = $operation($model);
        $this->invalidateFor($model);
        return $result;
    }
}
