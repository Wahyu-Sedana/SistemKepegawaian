<?php

namespace App\Policies;

use App\Models\Penggajian;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PenggajianPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_penggajian');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Penggajian $penggajian): bool
    {
        return $user->can('view_penggajian');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_penggajian');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Penggajian $penggajian): bool
    {
        return $user->can('update_penggajian');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Penggajian $penggajian): bool
    {
        return $user->can('delete_penggajian');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_penggajian');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Penggajian $penggajian): bool
    {
        return $user->can('restore_penggajian');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_penggajian');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Penggajian $penggajian): bool
    {
        return $user->can('replicate_penggajian');
    }

    /**
     * Determine whether the user can reorder models.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_penggajian');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Penggajian $penggajian): bool
    {
        return $user->can('force_delete_penggajian');
    }

    /**
     * Determine whether the user can permanently bulk delete models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_penggajian');
    }
}
