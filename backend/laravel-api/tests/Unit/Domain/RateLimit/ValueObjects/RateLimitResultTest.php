<?php

declare(strict_types=1);

use Carbon\Carbon;
use Ddd\Domain\RateLimit\ValueObjects\RateLimitResult;

describe('RateLimitResult ValueObject', function () {
    describe('正常系 - 許可時', function () {
        it('allowed()ファクトリメソッドで許可状態のResultを生成できる', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(5, 55, $resetAt);

            expect($result)->toBeInstanceOf(RateLimitResult::class)
                ->and($result->isAllowed())->toBeTrue()
                ->and($result->isBlocked())->toBeFalse()
                ->and($result->getAttempts())->toBe(5)
                ->and($result->getRemaining())->toBe(55)
                ->and($result->getResetAt())->toBe($resetAt);
        });

        it('残り回数が0でも許可状態を生成できる（境界値）', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(60, 0, $resetAt);

            expect($result->isAllowed())->toBeTrue()
                ->and($result->getRemaining())->toBe(0);
        });

        it('最初のリクエストを許可できる', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(1, 59, $resetAt);

            expect($result->getAttempts())->toBe(1)
                ->and($result->getRemaining())->toBe(59);
        });
    });

    describe('正常系 - 拒否時', function () {
        it('blocked()ファクトリメソッドで拒否状態のResultを生成できる', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::blocked(61, $resetAt);

            expect($result)->toBeInstanceOf(RateLimitResult::class)
                ->and($result->isAllowed())->toBeFalse()
                ->and($result->isBlocked())->toBeTrue()
                ->and($result->getAttempts())->toBe(61)
                ->and($result->getRemaining())->toBe(0) // blocked時は常に0
                ->and($result->getResetAt())->toBe($resetAt);
        });

        it('レート制限超過時の拒否状態を生成できる', function () {
            $resetAt = Carbon::now()->addMinutes(5);
            $result = RateLimitResult::blocked(100, $resetAt);

            expect($result->isBlocked())->toBeTrue()
                ->and($result->getAttempts())->toBe(100)
                ->and($result->getRemaining())->toBe(0);
        });
    });

    describe('相互排他性', function () {
        it('isAllowed()とisBlocked()は常に逆の値を返す（許可時）', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(5, 55, $resetAt);

            expect($result->isAllowed())->toBeTrue()
                ->and($result->isBlocked())->toBeFalse()
                ->and($result->isAllowed())->not->toBe($result->isBlocked());
        });

        it('isAllowed()とisBlocked()は常に逆の値を返す（拒否時）', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::blocked(61, $resetAt);

            expect($result->isAllowed())->toBeFalse()
                ->and($result->isBlocked())->toBeTrue()
                ->and($result->isAllowed())->not->toBe($result->isBlocked());
        });
    });

    describe('UNIXタイムスタンプ変換', function () {
        it('getResetTimestamp()はUNIXタイムスタンプを返す', function () {
            $resetAt = Carbon::now()->addMinutes(5);
            $result = RateLimitResult::allowed(5, 55, $resetAt);

            expect($result->getResetTimestamp())->toBe($resetAt->timestamp)
                ->and($result->getResetTimestamp())->toBeInt();
        });

        it('リセット時刻が未来の場合、現在時刻より大きいタイムスタンプを返す', function () {
            $resetAt = Carbon::now()->addMinutes(10);
            $result = RateLimitResult::allowed(5, 55, $resetAt);
            $now = Carbon::now()->timestamp;

            expect($result->getResetTimestamp())->toBeGreaterThan($now);
        });

        it('異なるリセット時刻は異なるタイムスタンプを返す', function () {
            $resetAt1 = Carbon::now()->addMinutes(1);
            $resetAt2 = Carbon::now()->addMinutes(5);
            $result1 = RateLimitResult::allowed(5, 55, $resetAt1);
            $result2 = RateLimitResult::allowed(5, 55, $resetAt2);

            expect($result1->getResetTimestamp())->not->toBe($result2->getResetTimestamp());
        });
    });

    describe('不変性', function () {
        it('readonly propertiesにより変更不可である', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(5, 55, $resetAt);

            expect(fn () => $result->allowed = false)
                ->toThrow(Error::class);
        });

        it('attemptsプロパティは変更不可である', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(5, 55, $resetAt);

            expect(fn () => $result->attempts = 100)
                ->toThrow(Error::class);
        });
    });

    describe('境界値テスト', function () {
        it('試行回数0で許可状態を生成できる', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(0, 60, $resetAt);

            expect($result->getAttempts())->toBe(0)
                ->and($result->getRemaining())->toBe(60);
        });

        it('残り回数が最大値（10000）の許可状態を生成できる', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(0, 10000, $resetAt);

            expect($result->getRemaining())->toBe(10000);
        });

        it('試行回数が最大値（10000）の拒否状態を生成できる', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::blocked(10000, $resetAt);

            expect($result->getAttempts())->toBe(10000)
                ->and($result->isBlocked())->toBeTrue();
        });
    });

    describe('HTTPヘッダー用データ', function () {
        it('X-RateLimit-Remainingヘッダー用に残り回数を取得できる', function () {
            $resetAt = Carbon::now()->addMinutes(1);
            $result = RateLimitResult::allowed(5, 55, $resetAt);

            expect($result->getRemaining())->toBe(55);
        });

        it('X-RateLimit-Resetヘッダー用にUNIXタイムスタンプを取得できる', function () {
            $resetAt = Carbon::create(2025, 10, 22, 12, 0, 0);
            $result = RateLimitResult::allowed(5, 55, $resetAt);

            expect($result->getResetTimestamp())->toBe($resetAt->timestamp);
        });

        it('Retry-Afterヘッダー用に再試行までの秒数を計算できる', function () {
            $now = Carbon::now();
            $resetAt = $now->copy()->addSeconds(55);
            $result = RateLimitResult::blocked(61, $resetAt);
            $retryAfter = abs($resetAt->diffInSeconds($now, false));

            // 55秒前後（テスト実行時間の誤差を考慮して54-56秒の範囲）
            expect($retryAfter)->toBeGreaterThanOrEqual(54)
                ->and($retryAfter)->toBeLessThanOrEqual(56);
        });
    });
});
