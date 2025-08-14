<?php

namespace Domain\User\Repositories;

use Domain\User\UserModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Support\Repositories\Repository;
use Support\Repositories\Traits\HasRepositoryCache;

/**
 * @extends Repository<UserModel>
 */
class UserSearchRepository extends Repository
{
    use HasRepositoryCache;

    public function __construct()
    {
        parent::__construct(new UserModel());
        $this->ttl = 600;
        $this->cachePrefix = 'user:search';
    }

    public function search(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if (empty($query)) {
            return new Collection();
        }

        return $this->remember(
            "query:" . md5($query) . ":limit:{$limit}",
            fn() => $this->query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(),
            300 // 5 минут
        );
    }

    public function searchPaginated(string $query, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = trim($query);

        return $this->query()
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, page: $page);
    }

    public function findVerified(): Collection
    {
        return $this->remember(
            "verified:all",
            fn() => $this->query()
                ->whereNotNull('email_verified_at')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }
}
