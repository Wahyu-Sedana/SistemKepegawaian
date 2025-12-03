<?php

namespace App\Policies;

use App\Models\KuotaCuti;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KuotaCutiPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua role bisa akses resource ini
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KuotaCuti $kuotaCuti): bool
    {
        // Admin tidak bisa view detail
        if ($user->hasRole('super_admin')) {
            return false;
        }

        // HRD dan Staff hanya bisa view data mereka sendiri
        return $kuotaCuti->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, KuotaCuti $kuotaCuti): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KuotaCuti $kuotaCuti): bool
    {
        return false;
    }
}
