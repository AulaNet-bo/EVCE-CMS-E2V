<?php

namespace App\Policies\Steve;

use App\Models\Steve\Station;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Station $station): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Station $station): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Station $station): bool
    {
        return $user->hasRole('super_admin');
    }
}
