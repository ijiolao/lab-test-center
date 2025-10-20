<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the order
     */
    public function view(User $user, Order $order): bool
    {
        // Users can view their own orders, admins can view all
        return $user->id === $order->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can create orders
     */
    public function create(User $user): bool
    {
        // All authenticated users can create orders
        return true;
    }

    /**
     * Determine if the user can update the order
     */
    public function update(User $user, Order $order): bool
    {
        // Users can update their own pending payment orders
        if ($user->id === $order->user_id && $order->status === 'pending_payment') {
            return true;
        }

        // Admins can update any order
        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete the order
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete orders
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can cancel the order
     */
    public function cancel(User $user, Order $order): bool
    {
        // Users can cancel their own uncollected orders
        if ($user->id === $order->user_id && $order->canBeCancelled()) {
            return true;
        }

        // Admins can cancel any cancellable order
        return $user->isAdmin() && $order->canBeCancelled();
    }

    /**
     * Determine if the user can mark order as collected
     */
    public function collect(User $user, Order $order): bool
    {
        // Only technicians and admins can collect specimens
        return $user->canCollectSpecimens();
    }

    /**
     * Determine if the user can submit order to lab
     */
    public function submitToLab(User $user, Order $order): bool
    {
        // Only technicians and admins can submit to lab
        return $user->canCollectSpecimens() && $order->canBeSubmittedToLab();
    }

    /**
     * Determine if the user can print specimen labels
     */
    public function print(User $user, Order $order): bool
    {
        // Technicians and admins can print labels
        return $user->canCollectSpecimens() && $order->can_be_printed;
    }
}