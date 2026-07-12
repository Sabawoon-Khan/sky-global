<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $this->notifications->recent($user),
            'unread_count' => $this->notifications->unreadCount($user),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $marked = $this->notifications->markAsRead($request->user(), $id);

        if (! $marked) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json([
            'unread_count' => $this->notifications->unreadCount($request->user()),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notifications->markAllAsRead($request->user());

        return response()->json(['unread_count' => 0]);
    }
}
