<?php

declare(strict_types=1);

use Ddd\Shared\Exceptions\ApplicationException;
use Ddd\Shared\Exceptions\DomainException;
use Ddd\Shared\Exceptions\InfrastructureException;

/**
 * Exception生成パフォーマンステスト
 *
 * DDD Exception階層（Domain, Application, Infrastructure）の
 * インスタンス生成時のパフォーマンスを計測します。
 *
 * パフォーマンス要件:
 * - Exception生成: 5ms以下
 *
 * Requirements: 非機能要件パフォーマンス.1
 * Task: 12.1
 */
describe('Exception生成パフォーマンステスト', function () {
    beforeEach(function () {
        // Request ID mockをセット
        request()->headers->set('X-Request-ID', '550e8400-e29b-41d4-a716-446655440000');
        request()->server->set('REQUEST_URI', '/api/v1/test');
    });

    describe('DomainException生成コスト', function () {
        it('DomainException生成が5ms以下で完了すること', function () {
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                // DomainException具象クラスを生成
                $exception = new class('Test domain exception message') extends DomainException
                {
                    public function getStatusCode(): int
                    {
                        return 400;
                    }

                    public function getErrorCode(): string
                    {
                        return 'DOMAIN-TEST-4001';
                    }

                    protected function getTitle(): string
                    {
                        return 'Test Domain Exception';
                    }
                };

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // Exceptionが正常に生成されたことを確認
                expect($exception)->toBeInstanceOf(DomainException::class);
            }

            // 平均生成時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均生成時間が5ms以下であること
            expect($avgTime)->toBeLessThan(5.0);
        });

        it('DomainException生成時にtoProblemDetails()呼び出しが5ms以下で完了すること', function () {
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $exception = new class('Test domain exception with problem details') extends DomainException
                {
                    public function getStatusCode(): int
                    {
                        return 400;
                    }

                    public function getErrorCode(): string
                    {
                        return 'DOMAIN-TEST-4002';
                    }

                    protected function getTitle(): string
                    {
                        return 'Test Domain Exception with Problem Details';
                    }
                };

                $startTime = microtime(true);

                // toProblemDetails()を呼び出す
                $problemDetails = $exception->toProblemDetails();

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('title');
                expect($problemDetails)->toHaveKey('status');
            }

            // 平均生成時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均生成時間が5ms以下であること
            expect($avgTime)->toBeLessThan(5.0);
        });
    });

    describe('ApplicationException生成コスト', function () {
        it('ApplicationException生成が5ms以下で完了すること', function () {
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                // ApplicationException具象クラスを生成
                $exception = new class('Test application exception message') extends ApplicationException
                {
                    public function getStatusCode(): int
                    {
                        return 404;
                    }

                    public function getErrorCode(): string
                    {
                        return 'APP-TEST-4001';
                    }

                    protected function getTitle(): string
                    {
                        return 'Test Application Exception';
                    }
                };

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // Exceptionが正常に生成されたことを確認
                expect($exception)->toBeInstanceOf(ApplicationException::class);
            }

            // 平均生成時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均生成時間が5ms以下であること
            expect($avgTime)->toBeLessThan(5.0);
        });

        it('ApplicationException生成時にtoProblemDetails()呼び出しが5ms以下で完了すること', function () {
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $exception = new class('Test application exception with problem details') extends ApplicationException
                {
                    public function getStatusCode(): int
                    {
                        return 404;
                    }

                    public function getErrorCode(): string
                    {
                        return 'APP-TEST-4002';
                    }

                    protected function getTitle(): string
                    {
                        return 'Test Application Exception with Problem Details';
                    }
                };

                $startTime = microtime(true);

                // toProblemDetails()を呼び出す
                $problemDetails = $exception->toProblemDetails();

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('title');
                expect($problemDetails)->toHaveKey('status');
            }

            // 平均生成時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均生成時間が5ms以下であること
            expect($avgTime)->toBeLessThan(5.0);
        });
    });

    describe('InfrastructureException生成コスト', function () {
        it('InfrastructureException生成が5ms以下で完了すること', function () {
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                // InfrastructureException具象クラスを生成
                $exception = new class('Test infrastructure exception message') extends InfrastructureException
                {
                    public function getStatusCode(): int
                    {
                        return 503;
                    }

                    public function getErrorCode(): string
                    {
                        return 'INFRA-TEST-5001';
                    }

                    protected function getTitle(): string
                    {
                        return 'Test Infrastructure Exception';
                    }
                };

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // Exceptionが正常に生成されたことを確認
                expect($exception)->toBeInstanceOf(InfrastructureException::class);
            }

            // 平均生成時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均生成時間が5ms以下であること
            expect($avgTime)->toBeLessThan(5.0);
        });

        it('InfrastructureException生成時にtoProblemDetails()呼び出しが5ms以下で完了すること', function () {
            $iterations = 100;
            $totalTime = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $exception = new class('Test infrastructure exception with problem details') extends InfrastructureException
                {
                    public function getStatusCode(): int
                    {
                        return 503;
                    }

                    public function getErrorCode(): string
                    {
                        return 'INFRA-TEST-5002';
                    }

                    protected function getTitle(): string
                    {
                        return 'Test Infrastructure Exception with Problem Details';
                    }
                };

                $startTime = microtime(true);

                // toProblemDetails()を呼び出す
                $problemDetails = $exception->toProblemDetails();

                $endTime = microtime(true);
                $totalTime += ($endTime - $startTime) * 1000; // ミリ秒

                // RFC 7807形式が正しく生成されたことを確認
                expect($problemDetails)->toHaveKey('type');
                expect($problemDetails)->toHaveKey('title');
                expect($problemDetails)->toHaveKey('status');
            }

            // 平均生成時間を計算
            $avgTime = $totalTime / $iterations;

            // 平均生成時間が5ms以下であること
            expect($avgTime)->toBeLessThan(5.0);
        });
    });

    describe('複数Exception生成の連続実行パフォーマンス', function () {
        it('異なるException種別を連続生成しても安定したパフォーマンスを維持すること', function () {
            $iterations = 50;
            $responseTimes = [];

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);

                // 3種類のExceptionを順番に生成
                $domainException = new class('Domain exception') extends DomainException
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
                        return 'Mixed Test Domain Exception';
                    }
                };

                $appException = new class('Application exception') extends ApplicationException
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
                        return 'Mixed Test Application Exception';
                    }
                };

                $infraException = new class('Infrastructure exception') extends InfrastructureException
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
                        return 'Mixed Test Infrastructure Exception';
                    }
                };

                $endTime = microtime(true);
                $responseTimes[] = ($endTime - $startTime) * 1000; // ミリ秒

                // 全てのExceptionが正常に生成されたことを確認
                expect($domainException)->toBeInstanceOf(DomainException::class);
                expect($appException)->toBeInstanceOf(ApplicationException::class);
                expect($infraException)->toBeInstanceOf(InfrastructureException::class);
            }

            // 平均生成時間を計算
            $avgTime = array_sum($responseTimes) / count($responseTimes);

            // 平均生成時間が15ms以下であること（3つのException生成なので5ms * 3 = 15ms）
            expect($avgTime)->toBeLessThan(15.0);

            // 最大生成時間が30ms以内であること
            $maxTime = max($responseTimes);
            expect($maxTime)->toBeLessThan(30.0);

            // 標準偏差が小さいこと（パフォーマンスが安定していること）
            $variance = 0;
            foreach ($responseTimes as $time) {
                $variance += pow($time - $avgTime, 2);
            }
            $stdDev = sqrt($variance / count($responseTimes));

            // 標準偏差が平均の100%以内であること（CI環境での変動を考慮）
            expect($stdDev)->toBeLessThan($avgTime * 1.0);
        });
    });
});
