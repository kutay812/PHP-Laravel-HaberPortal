<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('users.view') || $user->hasRole(RoleName::Admin->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view') || $user->hasRole(RoleName::Admin->value);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create') || $user->hasRole(RoleName::Admin->value);
    }

    public function update(User $user, User $model): bool
    {
        if ($model->hasRole(RoleName::SuperAdmin->value) && !$user->hasRole(RoleName::SuperAdmin->value)) {
            return false;
        }

        return $user->can('users.edit') || $user->hasRole(RoleName::Admin->value);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->hasRole(RoleName::SuperAdmin->value) && !$user->hasRole(RoleName::SuperAdmin->value)) {
            return false;
        }

        return $user->can('users.delete') || $user->hasRole(RoleName::Admin->value);
    }
}
