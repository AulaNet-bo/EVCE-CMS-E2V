<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Auth\Access\Response;

class WalletTransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'accountant', 'kiosko']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WalletTransaction $walletTransaction): bool
    {
        return $user->hasRole(['super_admin', 'accountant', 'kiosko']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'accountant']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WalletTransaction $walletTransaction): bool
    {
        return $user->hasRole(['super_admin', 'accountant']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WalletTransaction $walletTransaction): bool
    {
        return $user->hasRole('super_admin');
    }
}
