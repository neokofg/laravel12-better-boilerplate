<?php

namespace Domain\User\Repositories;

use Domain\User\UserModel;
use Support\Repositories\Repository;
use Support\Repositories\Traits\HasRepositoryCache;
use Support\Repositories\Traits\HasRepositoryWrite;
use Illuminate\Support\Facades\DB;

/**
 * @extends Repository<UserModel>
 */
class UserWriteRepository extends Repository
{
    /**
     * @use HasRepositoryWrite<UserModel>
     */
    use HasRepositoryWrite;
    use HasRepositoryCache;

    public function __construct()
    {
        parent::__construct(new UserModel());
        $this->cachePrefix = 'user';
        $this->cacheKeyFields = ['id', 'email'];
    }

    public function verifyEmail(UserModel $user): bool
    {
        $result = $this->update($user, [
            'email_verified_at' => now()
        ]);

        $this->forgetByPattern('verified:*');

        return $result;
    }

    public function changePassword(UserModel $user, string $password): bool
    {
        return $this->update($user, [
            'password' => $password,
        ]);
    }
}
