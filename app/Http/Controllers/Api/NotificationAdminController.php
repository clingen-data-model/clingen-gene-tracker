<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notification;
use App\User;
use Illuminate\Http\Request;

class NotificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:200']])['search'] ?? '');
        $notifications = Notification::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('type', 'like', '%'.$search.'%')
                ->orWhereHasMorph('notifiable', [User::class], fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))))
            ->with('notifiable')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
        $notifications->getCollection()->transform(fn (Notification $notification) => $this->serialize($notification));

        return $notifications;
    }

    public function show(Notification $notification)
    {
        $notification->load('notifiable');
        return $this->serialize($notification);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->noContent();
    }

    private function serialize(Notification $notification): array
    {
        $rawData = $notification->getRawOriginal('data');
        $decodedData = json_decode($rawData ?? '', true);

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'readable_type' => $notification->readable_type,
            'recipient' => $notification->notifiable ? [
                'id' => $notification->notifiable->id,
                'name' => $notification->notifiable->name ?? null,
                'email' => $notification->notifiable->email ?? null,
            ] : null,
            'data' => is_array($decodedData) ? $decodedData : $rawData,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}
