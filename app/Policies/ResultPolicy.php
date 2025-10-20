<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    /**
     * Determine if the user can view any results
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the result
     */
    public function view(User $user, Result $result): bool
    {
        // Users can view their own results
        if ($user->id === $result->order->user_id) {
            // Critical results must be reviewed before patient can view
            if ($result->has_critical_values && !$result->is_reviewed) {
                return false;
            }
            return true;
        }

        // Admins and reviewers can view all results
        return $user->isAdmin();
    }

    /**
     * Determine if the user can review the result
     */
    public function review(User $user, Result $result): bool
    {
        // Only reviewers and admins can review results
        return $user->canReviewResults();
    }

    /**
     * Determine if the user can download the result PDF
     */
    public function download(User $user, Result $result): bool
    {
        // Same rules as viewing
        return $this->view($user, $result);
    }

    /**
     * Determine if the user can regenerate the PDF
     */
    public function regeneratePdf(User $user, Result $result): bool
    {
        // Only admins can regenerate PDFs
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can delete the result
     */
    public function delete(User $user, Result $result): bool
    {
        // Only admins can delete results
        return $user->role === 'admin';
    }
}