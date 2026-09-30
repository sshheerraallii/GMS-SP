<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** V4-P6: in-app notification (shared row, per-user read state). */
class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = ['type', 'title', 'body', 'url', 'audience', 'dedupe_key', 'emailed_at'];

    protected $casts = ['emailed_at' => 'datetime'];

    /** Audience tags the given user belongs to. */
    public static function audiencesFor(User $user): array
    {
        $tags = ['all'];
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Accountant'])) {
            $tags[] = 'finance';
        }
        if ($user->can('shifts.assign')) {
            $tags[] = 'ops';
        }
        return $tags;
    }

    public static function visibleTo(User $user)
    {
        $tags = self::audiencesFor($user);

        return static::query()
            ->where('created_at', '>=', $user->created_at ?? now()->subYear())
            ->where(function ($q) use ($tags) {
                foreach ($tags as $t) {
                    $q->orWhere('audience', 'like', '%,' . $t . ',%');
                }
            });
    }

    public static function unreadFor(User $user)
    {
        return static::visibleTo($user)->whereNotExists(function ($q) use ($user) {
            $q->select(\DB::raw(1))
                ->from('app_notification_reads as r')
                ->whereColumn('r.notification_id', 'app_notifications.id')
                ->where('r.user_id', $user->id);
        });
    }
}
