<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserActiveSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActiveAccountSessions
{
    public const MAX_ACTIVE_SESSIONS = 6;
    public const INACTIVITY_MINUTES = 30;

    public static function canStart(User $user): bool
    {
        self::cleanup($user->id);

        return UserActiveSession::query()
            ->where('user_id', $user->id)
            ->count() < self::MAX_ACTIVE_SESSIONS;
    }

    public static function start(User $user, Request $request, ?string $token = null): ?string
    {
        self::cleanup($user->id);

        $token ??= Str::random(64);

        if (UserActiveSession::query()
            ->where('session_token', $token)
            ->where('user_id', '!=', $user->id)
            ->exists()) {
            $token = Str::random(64);
        }

        $existing = UserActiveSession::query()
            ->where('user_id', $user->id)
            ->where('session_token', $token)
            ->first();

        if (! $existing && ! self::canStart($user)) {
            return null;
        }

        UserActiveSession::updateOrCreate(
            [
                'user_id' => $user->id,
                'session_token' => $token,
            ],
            [
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'ip_address' => mb_substr((string) $request->ip(), 0, 45),
                'started_at' => $existing?->started_at ?? now(),
                'last_seen_at' => now(),
            ]
        );

        self::syncUserLastSession($user->id);

        return $token;
    }

    public static function ensure(User $user, Request $request, string $token): bool
    {
        self::cleanup($user->id);

        if (UserActiveSession::query()
            ->where('session_token', $token)
            ->where('user_id', '!=', $user->id)
            ->exists()) {
            return false;
        }

        $exists = UserActiveSession::query()
            ->where('user_id', $user->id)
            ->where('session_token', $token)
            ->exists();

        if ($exists) {
            return true;
        }

        return self::start($user, $request, $token) !== null;
    }

    public static function touch(User $user, Request $request, string $token): void
    {
        self::cleanup($user->id);

        UserActiveSession::query()
            ->where('user_id', $user->id)
            ->where('session_token', $token)
            ->update([
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                'ip_address' => mb_substr((string) $request->ip(), 0, 45),
                'last_seen_at' => now(),
            ]);

        self::syncUserLastSession($user->id);
    }

    public static function finish(int $userId, ?string $token): void
    {
        self::cleanup($userId);

        $query = UserActiveSession::query()->where('user_id', $userId);

        if ($token) {
            $query->where('session_token', $token);
        }

        $query->delete();

        self::syncUserLastSession($userId);
    }

    public static function cleanup(int $userId): void
    {
        UserActiveSession::query()
            ->where('user_id', $userId)
            ->where('last_seen_at', '<', now()->subMinutes(self::INACTIVITY_MINUTES))
            ->delete();

        self::syncUserLastSession($userId);
    }

    private static function syncUserLastSession(int $userId): void
    {
        $latest = UserActiveSession::query()
            ->where('user_id', $userId)
            ->latest('last_seen_at')
            ->first();

        User::query()
            ->whereKey($userId)
            ->update([
                'last_seen_at' => $latest?->last_seen_at,
                'active_session_token' => $latest?->session_token,
                'active_session_started_at' => $latest?->started_at,
            ]);
    }
}
