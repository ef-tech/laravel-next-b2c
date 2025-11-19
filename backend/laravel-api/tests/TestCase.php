<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    /**
     * ISO 8601 UTC形式のタイムスタンプであることを検証
     *
     * @param  string  $timestamp  タイムスタンプ文字列
     * @param  string  $message  アサーション失敗時のメッセージ
     */
    protected function assertIso8601Timestamp(string $timestamp, string $message = ''): void
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/';

        $this->assertMatchesRegularExpression(
            $pattern,
            $timestamp,
            $message ?: "Expected ISO 8601 UTC timestamp format (YYYY-MM-DDTHH:MM:SS+00:00), got: {$timestamp}"
        );
    }

    /**
     * テスト用にタイムスタンプを固定
     *
     * @param  string  $datetime  固定する日時（例: '2025-11-06 17:19:19'）
     */
    protected function freezeTimeAt(string $datetime): Carbon
    {
        $frozen = Carbon::parse($datetime);
        Carbon::setTestNow($frozen);

        return $frozen;
    }

    /**
     * 固定したタイムスタンプを解除
     */
    protected function unfreezeTime(): void
    {
        Carbon::setTestNow();
    }

    /**
     * テスト終了後に自動的にタイムスタンプ固定を解除
     */
    protected function tearDown(): void
    {
        $this->unfreezeTime();
        parent::tearDown();
    }
}
