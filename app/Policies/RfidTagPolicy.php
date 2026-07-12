<?php

namespace App\Policies;

use App\Models\RfidTag;
use App\Models\User;

class RfidTagPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin', 'sales', 'kiosko']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RfidTag $rfidTag): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin', 'sales', 'kiosko']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin', 'sales']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RfidTag $rfidTag): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin', 'sales']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RfidTag $rfidTag): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin']);
    }
}
