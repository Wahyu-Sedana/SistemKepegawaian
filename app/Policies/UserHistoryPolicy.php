<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserHistoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user::history');
    }

    public function view(User $user, UserHistory $model): bool
    {
        return $user->can('view_user::history');
    }

    public function create(User $user): bool
    {
        return $user->can('create_user::history');
    }

    public function update(User $user, UserHistory $model): bool
    {
        return $user->can('update_user::history');
    }

    public function delete(User $user, UserHistory $model): bool
    {
        return $user->can('delete_user::history');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_user::history');
    }

    public function forceDelete(User $user, UserHistory $model): bool
    {
        return $user->can('force_delete_user::history');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_user::history');
    }

    public function restore(User $user, UserHistory $model): bool
    {
        return $user->can('restore_user::history');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_user::history');
    }

    public function replicate(User $user): bool
    {
        return $user->can('replicate_user::history');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_user::history');
    }
}
