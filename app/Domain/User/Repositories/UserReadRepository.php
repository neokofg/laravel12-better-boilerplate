<?php

namespace Domain\User\Repositories;

use Domain\User\UserModel;
use Illuminate\Database\Eloquent\Builder;
use Support\Repositories\Repository;
use Support\Repositories\Traits\HasRepositoryCache;
use Support\Repositories\Traits\HasRepositoryRead;

/**
 * @extends Repository<UserModel>
 */
class UserReadRepository extends Repository
{
    /**
     * @use HasRepositoryRead<UserModel>
     */
    use HasRepositoryRead;
    use HasRepositoryCache;

    public function __construct()
    {
        parent::__construct(new UserModel());
        $this->ttl = 7200;
        $this->cachePrefix = 'user';
        $this->cacheKeyFields = ['id', 'email'];
        $this->cacheInvalidationRules = [
            'email' => ['email:*'],
            'name' => ['search:*'],
        ];
    }

    public function findByEmail(string $email): ?UserModel
    {
        return $this->remember(
            "email:{$email}",
            fn() => $this->query()->whereEmail($email)->first()
        );
    }
}
