<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * V4-P6: creates in-app notifications and (optionally) a critical email
 * via the Resend HTTP API. Never throws: a notification/email problem must
 * not break the action that triggered it.
 */
class NotificationService
{
    /**
     * @param array<int,string> $audience  any of: all, finance, ops
     * @return AppNotification|null null when it was a duplicate or failed
     */
    public static function notify(
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $audience = ['all'],
        ?string $dedupeKey = null,
        bool $email = false
    ): ?AppNotification {
        try {
            $n = AppNotification::create([
                'type'       => $type,
                'title'      => mb_substr($title, 0, 255),
                'body'       => $body,
                'url'        => $url ? mb_substr($url, 0, 500) : null,
                'audience'   => ',' . implode(',', $audience) . ',',
                'dedupe_key' => $dedupeKey ? mb_substr($dedupeKey, 0, 191) : null,
            ]);
        } catch (QueryException $e) {
            return null; // duplicate dedupe_key (or DB problem): stay silent
        } catch (\Throwable $e) {
            Log::warning('Notification create failed: ' . $e->getMessage());
            return null;
        }

        if ($email) {
            self::emailCritical($n);
        }

        return $n;
    }

    /** Super Admin + Accountant users who have a contact email. */
    public static function emailRecipients(): array
    {
        return User::query()
            ->whereNotNull('contact_email')
            ->where('contact_email', '!=', '')
            ->get()
            ->filter(fn ($u) => $u->hasAnyRole(['Super Admin', 'Accountant']))
            ->pluck('contact_email')
            ->unique()
            ->values()
            ->all();
    }

    private static function emailCritical(AppNotification $n): void
    {
        $key  = config('services.resend.key');
        $from = config('services.resend.from');
        if (!$key || !$from) {
            return; // email stays off until configured
        }

        $to = self::emailRecipients();
        if (!$to) {
            return;
        }

        try {
            $link = $n->url ? url($n->url) : null;
            $html = '<p><strong>' . e($n->title) . '</strong></p>'
                . ($n->body ? '<p>' . nl2br(e($n->body)) . '</p>' : '')
                . ($link ? '<p><a href="' . e($link) . '">Open in GMS</a></p>' : '');

            $res = Http::withToken($key)
                ->timeout(8)
                ->post('https://api.resend.com/emails', [
                    'from'    => $from,
                    'to'      => array_slice($to, 0, 50),
                    'subject' => '[GMS] ' . $n->title,
                    'html'    => $html,
                ]);

            if ($res->successful()) {
                $n->forceFill(['emailed_at' => now()])->save();
            } else {
                Log::warning('Resend email failed: ' . $res->status() . ' ' . $res->body());
            }
        } catch (\Throwable $e) {
            Log::warning('Resend email error: ' . $e->getMessage());
        }
    }
}
