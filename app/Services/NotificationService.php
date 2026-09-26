<?php

namespace App\Services;

use App\Models\NotificationStored;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * In-app notifications stored in the `notifications_stored` table and shown
 * to admins via the notification bell. No mail/queue dependency.
 */
class NotificationService
{
    /**
     * Send a notification to a single user.
     */
    public static function notifyUser(
        User $user,
        string $title,
        ?string $body = null,
        ?string $type = null,
        ?string $link = null
    ): void {
        try {
            NotificationStored::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'link' => $link,
            ]);
        } catch (\Throwable $e) {
            report($e); // Never let notifications break the business operation.
        }
    }

    /**
     * Notify every active admin (owner + manager roles), optionally narrowed
     * to those holding at least one of the given permission slugs.
     */
    public static function notifyAdmins(
        string $title,
        ?string $body = null,
        ?string $type = null,
        ?string $link = null,
        ?array $permissionSlugs = null
    ): void {
        $query = User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($q) {
                $q->whereIn('slug', ['owner', 'manager']);
            });

        if (! empty($permissionSlugs)) {
            $query->where(function ($w) use ($permissionSlugs) {
                foreach ($permissionSlugs as $slug) {
                    $w->orWhereHas('role', function ($q) use ($slug) {
                        $q->whereHas('permissions', fn ($p) => $p->where('slug', $slug));
                    })->orWhereHas('directPermissions', fn ($p) => $p->where('slug', $slug));
                }
            });
        }

        $userIds = $query->pluck('users.id');

        if ($userIds->isEmpty()) {
            return;
        }

        try {
            foreach ($userIds as $userId) {
                NotificationStored::create([
                    'user_id' => $userId,
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'link' => $link,
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Mark every stored notification as read for the acting user.
     */
    public static function markAllRead(?int $userId = null): int
    {
        $userId ??= Auth::id();

        return NotificationStored::query()
            ->where('user_id', $userId)
            ->unread()
            ->update(['is_read' => true]);
    }
}
