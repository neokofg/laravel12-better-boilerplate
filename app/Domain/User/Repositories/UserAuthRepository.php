<?php

namespace Domain\User\Repositories;

use Domain\User\UserModel;
use Support\Repositories\Repository;
use Support\Repositories\Traits\HasRepositoryCache;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Repository<UserModel>
 */
class UserAuthRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(new UserModel());
    }

    public function findForAuth(string $email): ?UserModel
    {
        return $this->query()->whereEmail($email)->first();
    }

    public function verifyPassword(UserModel $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    public function createAuthToken(UserModel $user, string $name = 'auth-token'): string
    {
        $user->tokens()->where('name', $name)->delete();

        return $user->createToken($name)->plainTextToken;
    }

    public function revokeCurrentToken(UserModel $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function revokeAllTokens(UserModel $user): void
    {
        $user->tokens()->delete();
    }
}
