<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\ApplicationException;
use Ddd\Shared\Exceptions\DomainException;
use Ddd\Shared\Exceptions\InfrastructureException;

/**
 * RFC 7807変換パフォーマンステスト
 *
 * toProblemDetails()メソッドのパフォーマンスを詳細に計測します。
 * RFC 7807形式への変換処理が高速であることを検証します。
 *
 * パフォーマンス要件:
 * - toProblemDetails()実行: 3ms以下
 *
 * Requirements: 非機能要件パフォーマンス.3
 * Task: 12.3
 */
describe('RFC 7807変換パフォーマンステスト', function () {
    beforeEach(function () {
        // Request ID mockをセット
        request()->headers->set('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000');
        request()->server->set('REQUEST_URI', '/api/v1/test');
        app()->setLocale('ja');
    });

    describe('toProblemDetails()実行コスト', function () {
        it('DomainException::toProblemDetails()が3ms以下で完了すること', function () {
            $exception = new class('Test domain exception') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'DOMAIN-RFC-4001';
                }

                protected function getTitle(): string
                {
                    return 'Test Domain Exception';
                }
            };

            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $problemDetails = $exception->toProblemDetails();
                $endTime = microtime(true);

                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('title');
                expect($problemDetails)->toHaveKey('status');
                expect($problemDetails)->toHaveKey('detail');
                expect($problemDetails)->toHaveKey('error_code');
                expect($problemDetails)->toHaveKey('trace_id');
                expect($problemDetails)->toHaveKey('instance');
                expect($problemDetails)->toHaveKey('timestamp');
            }

            // 平均実行時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均実行時間が3ms以下であること
            expect($avgTime)->toBeLessThan(3.0);
        });

        it('ApplicationException::toProblemDetails()が3ms以下で完了すること', function () {
            $exception = new class('Test application exception') extends ApplicationException
            {
                public function getStatusCode(): int
                {
                    return 404;
                }

                public function getErrorCode(): string
                {
                    return 'APP-RFC-4001';
                }

                protected function getTitle(): string
                {
                    return 'Test Application Exception';
                }
            };

            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
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

            // 平均実行時間が3ms以下であること
            expect($avgTime)->toBeLessThan(3.0);
        });

        it('InfrastructureException::toProblemDetails()が3ms以下で完了すること', function () {
            $exception = new class('Test infrastructure exception') extends InfrastructureException
            {
                public function getStatusCode(): int
                {
                    return 503;
                }

                public function getErrorCode(): string
                {
                    return 'INFRA-RFC-5001';
                }

                protected function getTitle(): string
                {
                    return 'Test Infrastructure Exception';
                }
            };

            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
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

            // 平均実行時間が3ms以下であること
            expect($avgTime)->toBeLessThan(3.0);
        });
    });

    describe('RFC 7807フィールド生成コスト', function () {
        it('typeフィールド生成（エラーコードURIエンコード）が高速であること', function () {
            $exception = new class('Test exception') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'DOMAIN-TYPE-4001';
                }

                protected function getTitle(): string
                {
                    return 'Test Type Generation';
                }
            };

            $iterations = 200;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $problemDetails = $exception->toProblemDetails();
                $type = $problemDetails['type'];
                $endTime = microtime(true);

                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // typeフィールドが正しく生成されていること
                expect($type)->toContain(config('app.url'));
                expect($type)->toContain('/errors/');
                expect($type)->toContain('domain-type-4001'); // 小文字変換されること
            }

            // 平均実行時間を計算
            $avgTime = $totalTime / $iterations;

            // typeフィールド生成が2ms以下であること
            expect($avgTime)->toBeLessThan(2.0);
        });

        it('timestampフィールド生成（ISO 8601 Zulu形式）が高速であること', function () {
            $exception = new class('Test exception') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'DOMAIN-TIMESTAMP-4001';
                }

                protected function getTitle(): string
                {
                    return 'Test Timestamp Generation';
                }
            };

            $iterations = 200;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $problemDetails = $exception->toProblemDetails();
                $timestamp = $problemDetails['timestamp'];
                $endTime = microtime(true);

                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // timestampフィールドがISO 8601 Zulu形式であること
                expect($timestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/');
            }

            // 平均実行時間を計算
            $avgTime = $totalTime / $iterations;

            // timestampフィールド生成が2ms以下であること
            expect($avgTime)->toBeLessThan(2.0);
        });

        it('trace_idフィールド取得（Request IDヘッダー）が高速であること', function () {
            $exception = new class('Test exception') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'DOMAIN-TRACEID-4001';
                }

                protected function getTitle(): string
                {
                    return 'Test Trace ID Generation';
                }
            };

            $iterations = 200;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $problemDetails = $exception->toProblemDetails();
                $traceId = $problemDetails['trace_id'];
                $endTime = microtime(true);

                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // trace_idフィールドがRequest IDと一致すること
                expect($traceId)->toBe('550e8400-e29b-41d4-a716-446655440000');
            }

            // 平均実行時間を計算
            $avgTime = $totalTime / $iterations;

            // trace_idフィールド取得が1ms以下であること
            expect($avgTime)->toBeLessThan(1.0);
        });
    });

    describe('複数回変換のパフォーマンス安定性', function () {
        it('同一Exceptionに対して複数回toProblemDetails()を呼び出しても安定したパフォーマンスを維持すること', function () {
            $exception = new class('Test exception for multiple calls') extends DomainException
            {
                public function getStatusCode(): int
                {
                    return 400;
                }

                public function getErrorCode(): string
                {
                    return 'DOMAIN-MULTIPLE-4001';
                }

                protected function getTitle(): string
                {
                    return 'Test Multiple Calls';
                }
            };

            $iterations = 500;
            $responseTimes = [];

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $problemDetails = $exception->toProblemDetails();
                $endTime = microtime(true);

                $responseTimes[] = ($endTime - $startTime) * 1000;

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('title');
            }

            // 統計情報を計算
            $avgTime = array_sum($responseTimes) / count($responseTimes);
            $maxTime = max($responseTimes);
            $minTime = min($responseTimes);

            // 平均実行時間が3ms以下であること
            expect($avgTime)->toBeLessThan(3.0);

            // 最大実行時間が10ms以下であること
            expect($maxTime)->toBeLessThan(10.0);

            // 最小実行時間が0より大きいこと
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

    describe('大量のRFC 7807変換処理', function () {
        it('異なるExceptionに対して1000回のRFC 7807変換を実行してもパフォーマンスが劣化しないこと', function () {
            $iterations = 1000;
            $responseTimes = [];

            for ($i = 0; $i < $iterations; $i++) {
                // 毎回新しいExceptionインスタンスを生成
                $exception = new class("Test exception {$i}") extends DomainException
                {
                    public function getStatusCode(): int
                    {
                        return 400;
                    }

                    public function getErrorCode(): string
                    {
                        return 'DOMAIN-LOAD-4001';
                    }

                    protected function getTitle(): string
                    {
                        return 'Test Load';
                    }
                };

                $startTime = microtime(true);
                $problemDetails = $exception->toProblemDetails();
                $endTime = microtime(true);

                $responseTimes[] = ($endTime - $startTime) * 1000;

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('detail');
            }

            // 統計情報を計算
            $avgTime = array_sum($responseTimes) / count($responseTimes);
            $maxTime = max($responseTimes);

            // 平均実行時間が3ms以下であること
            expect($avgTime)->toBeLessThan(3.0);

            // 最大実行時間が15ms以下であること
            expect($maxTime)->toBeLessThan(15.0);
        });

        it('全Exception種別（Domain/Application/Infrastructure）を混合して1000回変換してもパフォーマンスが安定していること', function () {
            $iterations = 1000;
            $responseTimes = [];

            for ($i = 0; $i < $iterations; $i++) {
                // Exception種別をランダムに選択
                $exceptionType = $i % 3;

                if ($exceptionType === 0) {
                    // DomainException
                    $exception = new class("Domain exception {$i}") extends DomainException
                    {
                        public function getStatusCode(): int
                        {
                            return 400;
                        }

                        public function getErrorCode(): string
                        {
                            return 'DOMAIN-MIXED-4001';
                        }

                        protected function getTitle(): string
                        {
                            return 'Mixed Domain Exception';
                        }
                    };
                } elseif ($exceptionType === 1) {
                    // ApplicationException
                    $exception = new class("Application exception {$i}") extends ApplicationException
                    {
                        public function getStatusCode(): int
                        {
                            return 404;
                        }

                        public function getErrorCode(): string
                        {
                            return 'APP-MIXED-4001';
                        }

                        protected function getTitle(): string
                        {
                            return 'Mixed Application Exception';
                        }
                    };
                } else {
                    // InfrastructureException
                    $exception = new class("Infrastructure exception {$i}") extends InfrastructureException
                    {
                        public function getStatusCode(): int
                        {
                            return 503;
                        }

                        public function getErrorCode(): string
                        {
                            return 'INFRA-MIXED-5001';
                        }

                        protected function getTitle(): string
                        {
                            return 'Mixed Infrastructure Exception';
                        }
                    };
                }

                $startTime = microtime(true);
                $problemDetails = $exception->toProblemDetails();
                $endTime = microtime(true);

                $responseTimes[] = ($endTime - $startTime) * 1000;

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('status');
            }

            // 統計情報を計算
            $avgTime = array_sum($responseTimes) / count($responseTimes);
            $maxTime = max($responseTimes);

            // 平均実行時間が3ms以下であること
            expect($avgTime)->toBeLessThan(3.0);

            // 最大実行時間が15ms以下であること
            expect($maxTime)->toBeLessThan(15.0);

            // 標準偏差を計算
            $variance = 0;
            foreach ($responseTimes as $time) {
                $variance += pow($time - $avgTime, 2);
            }
            $stdDev = sqrt($variance / count($responseTimes));

            // 標準偏差が平均の100%以内であること
            expect($stdDev)->toBeLessThan($avgTime * 1.0);
        });
    });
});
