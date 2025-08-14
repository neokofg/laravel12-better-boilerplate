<?php

namespace Domain\User;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $name
 * @property string $email
 * @property Carbon $email_verified_at
 * @property string $password
 *
 * @method Builder<UserModel>|UserModel whereName($value)
 * @method Builder<UserModel>|UserModel whereEmail($value)
 * @method Builder<UserModel>|UserModel whereEmailVerifiedAt($value)
 */
class UserModel extends Model
{
    use HasUlids;
    use HasApiTokens;

    protected $table = 'users';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }
}
