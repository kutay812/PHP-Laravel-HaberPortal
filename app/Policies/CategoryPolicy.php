<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('categories.view') || $user->hasAnyRole(RoleName::adminRoles());
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.view') || $user->hasAnyRole(RoleName::adminRoles());
    }

    public function create(User $user): bool
    {
        return $user->can('categories.create') || $user->hasAnyRole(RoleName::adminRoles());
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.edit') || $user->hasAnyRole(RoleName::adminRoles());
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.delete') || $user->hasAnyRole(RoleName::adminRoles());
    }
}
