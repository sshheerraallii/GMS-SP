<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\PayDateNotifier;
use Illuminate\Support\Facades\DB;

/** V4-P6: bell feed, list page, read state. */
class NotificationController extends Controller
{
    public function feed()
    {
        $user = auth()->user();

        try {
            PayDateNotifier::run();
        } catch (\Throwable $e) {
            report($e);
        }

        $unread = AppNotification::unreadFor($user)->count();
        $readIds = DB::table('app_notification_reads')->where('user_id', $user->id)->pluck('notification_id')->flip();

        $items = AppNotification::visibleTo($user)->orderByDesc('id')->limit(10)->get()
            ->map(fn ($n) => [
                'id'     => $n->id,
                'type'   => $n->type,
                'title'  => $n->title,
                'body'   => $n->body,
                'url'    => $n->url,
                'read'   => isset($readIds[$n->id]),
                'when'   => $n->created_at->timezone('Europe/London')->format('d M H:i'),
            ]);

        return response()->json(['unread' => $unread, 'items' => $items]);
    }

    public function index()
    {
        $user = auth()->user();
        $notifications = AppNotification::visibleTo($user)->orderByDesc('id')->paginate(30);
        $readIds = DB::table('app_notification_reads')->where('user_id', $user->id)->pluck('notification_id')->flip();

        return view('notifications.index', compact('notifications', 'readIds'));
    }

    public function read(int $id)
    {
        $user = auth()->user();
        $n = AppNotification::visibleTo($user)->whereKey($id)->first();
        if ($n) {
            DB::table('app_notification_reads')->updateOrInsert(
                ['user_id' => $user->id, 'notification_id' => $n->id],
                ['read_at' => now()]
            );
        }
        return response()->json(['ok' => true]);
    }

    public function readAll()
    {
        $user = auth()->user();
        $ids = AppNotification::unreadFor($user)->pluck('app_notifications.id');
        $rows = $ids->map(fn ($i) => ['user_id' => $user->id, 'notification_id' => $i, 'read_at' => now()])->all();
        if ($rows) {
            DB::table('app_notification_reads')->insertOrIgnore($rows);
        }
        return request()->wantsJson() ? response()->json(['ok' => true]) : back();
    }
}
