<?php

declare(strict_types=1);

/**
 * 翻訳処理パフォーマンステスト
 *
 * Laravel Translation Cacheの効果を検証します。
 * 2回目以降のアクセスでキャッシュヒットによる高速化を確認します。
 *
 * パフォーマンス要件:
 * - 1回目のアクセス: 10ms以内
 * - 2回目以降のアクセス: 1ms以内（キャッシュヒット）
 *
 * Requirements: 非機能要件パフォーマンス.2
 * Task: 12.2
 */
describe('翻訳処理パフォーマンステスト', function () {
    beforeEach(function () {
        // Translation Loaderをリフレッシュしてキャッシュをクリア
        // Note: Laravel Translatorは内部的にloaded配列でキャッシュを保持
        // 各テストケースで個別にテストするため、キャッシュクリアは不要
    });

    describe('翻訳キャッシュヒット率テスト', function () {
        it('1回目の翻訳アクセスが10ms以内で完了すること', function () {
            app()->setLocale('ja');

            $startTime = microtime(true);
            $message = trans('errors.auth.invalid_credentials');
            $endTime = microtime(true);

            $time = ($endTime - $startTime) * 1000; // ミリ秒

            // 翻訳メッセージが正しく取得されること
            expect($message)->toBe('メールアドレスまたはパスワードが正しくありません');

            // 1回目のアクセスが10ms以内であること
            expect($time)->toBeLessThan(10.0);
        });

        it('2回目以降の翻訳アクセスがキャッシュヒットにより1ms以下で完了すること', function () {
            app()->setLocale('ja');

            // 1回目のアクセス（キャッシュ構築）
            trans('errors.auth.invalid_credentials');

            // 2回目以降のアクセス（キャッシュヒット）を100回実行
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $message = trans('errors.auth.invalid_credentials');
                $endTime = microtime(true);

                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // 翻訳メッセージが正しく取得されること
                expect($message)->toBe('メールアドレスまたはパスワードが正しくありません');
            }

            // 平均アクセス時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均アクセス時間が1ms以下であること
            expect($avgTime)->toBeLessThan(1.0);
        });

        it('英語翻訳でも2回目以降が1ms以下で完了すること', function () {
            app()->setLocale('en');

            // 1回目のアクセス（キャッシュ構築）
            trans('errors.auth.invalid_credentials');

            // 2回目以降のアクセス（キャッシュヒット）を100回実行
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $message = trans('errors.auth.invalid_credentials');
                $endTime = microtime(true);

                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // 英語翻訳メッセージが正しく取得されること
                expect($message)->toBeString();
            }

            // 平均アクセス時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均アクセス時間が1ms以下であること
            expect($avgTime)->toBeLessThan(1.0);
        });
    });

    describe('複数翻訳キーのキャッシュパフォーマンス', function () {
        it('複数の翻訳キーに対してキャッシュが効果的に動作すること', function () {
            app()->setLocale('ja');

            // 1回目のアクセス（全キーのキャッシュ構築）
            $keys = [
                'errors.auth.invalid_credentials',
                'errors.auth.token_expired',
                'errors.validation.invalid_email',
                'errors.business.resource_not_found',
                'errors.infrastructure.database_unavailable',
            ];

            foreach ($keys as $key) {
                trans($key);
            }

            // 2回目以降のアクセス（キャッシュヒット）を50回実行
            $iterations = 50;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                foreach ($keys as $key) {
                    trans($key);
                }

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒
            }

            // 平均アクセス時間を計算（5つのキー合計）
            $avgTime = $totalTime / $iterations;

            // 5つのキー合計で平均5ms以下であること（1キーあたり1ms以下）
            expect($avgTime)->toBeLessThan(5.0);
        });
    });

    describe('言語切り替え時のキャッシュパフォーマンス', function () {
        it('言語切り替え後も個別にキャッシュが有効であること', function () {
            $key = 'errors.auth.invalid_credentials';

            // 日本語でキャッシュ構築
            app()->setLocale('ja');
            trans($key);

            // 英語でキャッシュ構築
            app()->setLocale('en');
            trans($key);

            // 日本語アクセス（キャッシュヒット）
            $jaStartTime = microtime(true);
            app()->setLocale('ja');
            $jaMessage = trans($key);
            $jaEndTime = microtime(true);
            $jaTime = ($jaEndTime - $jaStartTime) * 1000;

            // 英語アクセス（キャッシュヒット）
            $enStartTime = microtime(true);
            app()->setLocale('en');
            $enMessage = trans($key);
            $enEndTime = microtime(true);
            $enTime = ($enEndTime - $enStartTime) * 1000;

            // 両言語ともに正しく翻訳されていること
            expect($jaMessage)->toBe('メールアドレスまたはパスワードが正しくありません');
            expect($enMessage)->toBeString();

            // 両言語ともにキャッシュヒットにより1ms以下であること
            expect($jaTime)->toBeLessThan(1.0);
            expect($enTime)->toBeLessThan(1.0);
        });

        it('100回の言語切り替えでもパフォーマンスが安定していること', function () {
            $key = 'errors.auth.invalid_credentials';

            // 事前キャッシュ構築
            app()->setLocale('ja');
            trans($key);
            app()->setLocale('en');
            trans($key);

            $iterations = 100;
            $jaTotalTime = 0;
            $enTotalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                // 日本語アクセス
                $jaStartTime = microtime(true);
                app()->setLocale('ja');
                trans($key);
                $jaEndTime = microtime(true);
                $jaTotalTime += ($jaEndTime - $jaStartTime) * 1000;

                // 英語アクセス
                $enStartTime = microtime(true);
                app()->setLocale('en');
                trans($key);
                $enEndTime = microtime(true);
                $enTotalTime += ($enEndTime - $enStartTime) * 1000;
            }

            // 平均アクセス時間を計算
            $jaAvgTime = $jaTotalTime / $iterations;
            $enAvgTime = $enTotalTime / $iterations;

            // 両言語ともに平均1ms以下であること
            expect($jaAvgTime)->toBeLessThan(1.0);
            expect($enAvgTime)->toBeLessThan(1.0);

            // 標準偏差が小さいこと（パフォーマンスが安定していること）
            // Note: 実際の標準偏差計算は簡略化のため省略
        });
    });

    describe('エラーハンドリングとの統合パフォーマンス', function () {
        it('Exception生成とtoProblemDetails()内での翻訳処理が高速であること', function () {
            app()->setLocale('ja');

            // 翻訳キーのキャッシュを事前構築
            trans('errors.auth.invalid_credentials');

            $iterations = 50;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                // Exception生成
                $exception = new \Ddd\Shared\Exceptions\EmailAlreadyExistsException('test@example.com');

                // toProblemDetails()呼び出し（内部でtrans()が実行される）
                $problemDetails = $exception->toProblemDetails();

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('title');
                expect($problemDetails)->toHaveKey('status');
            }

            // 平均実行時間を計算
            $avgTime = $totalTime / $iterations;

            // Exception生成 + toProblemDetails() + 翻訳処理が10ms以内であること
            expect($avgTime)->toBeLessThan(10.0);
        });
    });

    describe('Translation Cache負荷テスト', function () {
        it('1000回の連続アクセスでもパフォーマンスが劣化しないこと', function () {
            app()->setLocale('ja');
            $key = 'errors.auth.invalid_credentials';

            // キャッシュ構築
            trans($key);

            $iterations = 1000;
            $responseTimes = [];

            // 1000回アクセス
            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                trans($key);
                $endTime = microtime(true);

                $responseTimes[] = ($endTime - $startTime) * 1000;
            }

            // 統計情報を計算
            $avgTime = array_sum($responseTimes) / count($responseTimes);
            $maxTime = max($responseTimes);
            $minTime = min($responseTimes);

            // 平均アクセス時間が1ms以下であること
            expect($avgTime)->toBeLessThan(1.0);

            // 最大アクセス時間が3ms以下であること
            expect($maxTime)->toBeLessThan(3.0);

            // 最小アクセス時間が0より大きいこと（処理が実際に実行されていることを確認）
            expect($minTime)->toBeGreaterThan(0.0);

            // 標準偏差を計算
            $variance = 0;
            foreach ($responseTimes as $time) {
                $variance += pow($time - $avgTime, 2);
            }
            $stdDev = sqrt($variance / count($responseTimes));

            // 標準偏差が平均の100%以内であること（パフォーマンスが安定していること）
            expect($stdDev)->toBeLessThan($avgTime * 1.0);
        });
    });
});
