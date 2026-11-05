<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\News;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('news.view') || $user->hasAnyRole(RoleName::staffRoles());
    }

    public function view(User $user, News $news): bool
    {
        return $user->can('news.view') || $user->hasAnyRole(RoleName::staffRoles());
    }

    public function create(User $user): bool
    {
        return $user->can('news.create') || $user->hasAnyRole(RoleName::staffRoles());
    }

    public function update(User $user, News $news): bool
    {
        return $user->can('news.edit') || $user->hasAnyRole(RoleName::staffRoles());
    }

    public function delete(User $user, News $news): bool
    {
        return $user->can('news.delete') || $user->hasAnyRole(RoleName::staffRoles());
    }
}
