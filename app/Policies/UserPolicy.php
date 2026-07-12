<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
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
    public function view(User $user, User $model): bool
    {
        // Prevent non-super-admins from seeing super-admins
        if ($model->hasRole('super_admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'staff_admin', 'sales', 'kiosko']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Prevent editing super-admins unless you are one
        if ($model->hasRole('super_admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete super_admin
        if ($model->hasRole('super_admin')) {
            return false;
        }

        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasRole(['super_admin', 'staff_admin']);
    }
}
