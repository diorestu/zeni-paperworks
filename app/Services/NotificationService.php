<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function notifyUser(User $user, array $payload): AppNotification
    {
        return AppNotification::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => $payload['type'] ?? 'general',
            'title' => $payload['title'] ?? 'Notification',
            'message' => $payload['message'] ?? '',
            'href' => $payload['href'] ?? null,
            'icon' => $payload['icon'] ?? 'si:notifications-line',
            'payload' => $payload['payload'] ?? null,
            'read_at' => null,
        ]);
    }
}
