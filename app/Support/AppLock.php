<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Senha opcional do aplicativo. Ela protege a abertura do ZapVault, não criptografa os dados.
 *
 * A sessão desbloqueada guarda a hora do desbloqueio e uma marca do hash da senha: trocar a
 * senha bloqueia as outras sessões, e o desbloqueio vale no máximo 12 horas.
 */
class AppLock
{
    public const MAX_HOURS = 12;

    /** Tentativas erradas toleradas antes do atraso crescente. */
    public const FREE_ATTEMPTS = 5;

    public const MAX_DELAY = 60;

    private const FAILURES_KEY = 'zapvault.unlock.failures';

    private const UNTIL_KEY = 'zapvault.unlock.until';

    public static function enabled(): bool
    {
        return self::hash() !== null;
    }

    public static function isUnlocked(Session $session): bool
    {
        $hash = self::hash();

        if ($hash === null) {
            return true;
        }

        $at = (int) $session->get('zapvault.unlocked_at', 0);

        return $at > 0
            && now()->getTimestamp() - $at < self::MAX_HOURS * 3600
            && hash_equals(self::mark($hash), (string) $session->get('zapvault.unlock_mark', ''));
    }

    public static function unlock(Session $session): void
    {
        $session->regenerate();
        $session->put('zapvault.unlocked_at', now()->getTimestamp());
        $session->put('zapvault.unlock_mark', self::mark((string) self::hash()));
    }

    public static function lock(Session $session): void
    {
        $session->forget(['zapvault.unlocked_at', 'zapvault.unlock_mark']);
        $session->regenerate();
    }

    public static function setPassword(string $password, Session $session): void
    {
        Setting::put('password_hash', Hash::make($password));
        self::unlock($session);
    }

    public static function removePassword(): void
    {
        Setting::query()->whereKey('password_hash')->delete();
        self::resetFailures();
    }

    /**
     * Confere a senha respeitando o atraso entre tentativas.
     *
     * @return array{ok: bool, retry_after: int}
     */
    public static function check(string $password): array
    {
        $wait = self::retryAfter();

        if ($wait > 0) {
            return ['ok' => false, 'retry_after' => $wait];
        }

        $hash = self::hash();

        if ($hash !== null && Hash::check($password, $hash)) {
            self::resetFailures();

            return ['ok' => true, 'retry_after' => 0];
        }

        $failures = (int) Cache::get(self::FAILURES_KEY, 0) + 1;
        Cache::put(self::FAILURES_KEY, $failures, now()->addHour());

        $delay = self::delayFor($failures);
        if ($delay > 0) {
            Cache::put(self::UNTIL_KEY, now()->getTimestamp() + $delay, now()->addSeconds($delay + 5));
        }

        return ['ok' => false, 'retry_after' => $delay];
    }

    /** Segundos de espera depois de N senhas erradas seguidas: 5, 10, 20, 40 e depois 60. */
    public static function delayFor(int $failures): int
    {
        if ($failures < self::FREE_ATTEMPTS) {
            return 0;
        }

        return min(self::MAX_DELAY, 5 * 2 ** ($failures - self::FREE_ATTEMPTS));
    }

    public static function retryAfter(): int
    {
        return max(0, (int) Cache::get(self::UNTIL_KEY, 0) - now()->getTimestamp());
    }

    private static function resetFailures(): void
    {
        Cache::forget(self::FAILURES_KEY);
        Cache::forget(self::UNTIL_KEY);
    }

    private static function hash(): ?string
    {
        $hash = Setting::get('password_hash');

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    private static function mark(string $hash): string
    {
        return hash('sha256', $hash);
    }
}
