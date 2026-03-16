<?php

namespace App\Observers;

use App\Models\Promotion;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;

class PromotionObserver
{
    /**
     * Handle the Promotion "created" event.
     */
    public function created(Promotion $promotion): void
    {
        $this->checkAndBroadcast($promotion);
    }

    /**
     * Handle the Promotion "updated" event.
     */
    public function updated(Promotion $promotion): void
    {
        $this->checkAndBroadcast($promotion);
    }

    protected function checkAndBroadcast(Promotion $promotion): void
    {
        // Only broadcast if: active, type push, not caducada, and NOT previously broadcasted
        if (
            $promotion->is_active &&
            $promotion->type === 'push' &&
            $promotion->broadcasted_at === null &&
            ($promotion->end_at === null || $promotion->end_at->isFuture())
        ) {
            // Find all users with FCM tokens
            $users = User::whereNotNull('fcm_token')->get();

            if ($users->isNotEmpty()) {
                Notification::send($users, new GeneralNotification(
                    $promotion->title,
                    $promotion->body,
                    [
                        'promotion_id' => $promotion->id,
                        'image_url' => $promotion->image_path ? asset('storage/' . $promotion->image_path) : null,
                        'type' => 'promotion'
                    ]
                ));

                // Mark as broadcasted to avoid duplicates on future edits
                $promotion->broadcasted_at = now();
                $promotion->saveQuietly();
            }
        }
    }
}
