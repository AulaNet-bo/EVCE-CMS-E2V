<?php

namespace App\Policies\Steve;

use App\Models\Steve\ChargingSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChargingSessionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChargingSession $chargingSession): bool
    {
        return $user->hasRole('super_admin');
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
    public function update(User $user, ChargingSession $chargingSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChargingSession $chargingSession): bool
    {
        return false;
    }
}
