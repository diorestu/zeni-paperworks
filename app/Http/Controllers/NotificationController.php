<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = $request->string('status')->toString() === 'unread' ? 'unread' : 'all';

        $query = AppNotification::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->latest();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(20)->withQueryString();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->through(fn (AppNotification $item) => $this->transform($item)),
            'filters' => [
                'status' => $status,
            ],
            'unreadCount' => AppNotification::query()
                ->where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = AppNotification::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (AppNotification $item) => $this->transform($item));

        $unreadCount = AppNotification::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $notification->user_id === $user->id && $notification->company_id === $user->company_id,
            403
        );

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        AppNotification::query()
            ->where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
        ]);
    }

    private function transform(AppNotification $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'icon' => $item->icon,
            'title' => $item->title,
            'message' => $item->message,
            'href' => $item->href,
            'is_read' => (bool) $item->read_at,
            'occurred_at' => optional($item->created_at)->toIso8601String(),
        ];
    }
}
