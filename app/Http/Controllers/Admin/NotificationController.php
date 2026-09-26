<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationStored;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Full notifications page.
     */
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notificationsStored()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Notifications', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Lightweight JSON payload used by the bell to poll for new notifications.
     */
    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadStoredNotifications()->count(),
            'items' => $request->user()->notificationsStored()
                ->latest('id')
                ->limit(10)
                ->get(['id', 'title', 'body', 'type', 'link', 'is_read', 'created_at']),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function read(Request $request, NotificationStored $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * Mark all of the current user's notifications as read.
     */
    public function readAll(Request $request): JsonResponse
    {
        NotificationService::markAllRead($request->user()->id);

        return response()->json(['ok' => true]);
    }
}
