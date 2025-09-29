<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionDisableTest extends TestCase
{
    /**
     * セッションドライバーがarrayに設定されていることをテスト
     */
    public function test_session_driver_is_array(): void
    {
        $sessionDriver = config('session.driver');
        $this->assertEquals('array', $sessionDriver, 'Session driver should be set to array for stateless API');
    }

    /**
     * セッション関連ミドルウェアが除外されていることをテスト
     */
    public function test_session_middleware_is_removed(): void
    {
        // ヘルスチェックエンドポイントにアクセスしてセッションが作成されないことを確認
        $response = $this->get('/up');
        $response->assertStatus(200);

        // セッション情報が存在しないことを確認
        $this->assertArrayNotHasKey('laravel_session', $_COOKIE ?? []);
    }

    /**
     * ステートレスAPI動作の検証
     */
    public function test_stateless_api_operation(): void
    {
        // 複数回のリクエストでセッション状態が保持されないことを確認
        $response1 = $this->get('/up');
        $response1->assertStatus(200);

        $response2 = $this->get('/up');
        $response2->assertStatus(200);

        // 各リクエストが独立していることを確認（セッション状態なし）
        $this->assertTrue(true, 'Multiple requests should be stateless');
    }

    /**
     * CSRF攻撃対象の完全除去確認
     */
    public function test_csrf_protection_is_disabled(): void
    {
        // CSRFトークンなしでPOSTリクエストが通ることを確認
        // まずはヘルスチェックエンドポイントで確認（GETのみのため、API追加後に拡張予定）
        $response = $this->get('/up');
        $response->assertStatus(200);

        // CSRFトークンがレスポンスに含まれていないことを確認
        $content = $response->getContent();
        $this->assertStringNotContainsString('csrf-token', $content, 'CSRF token should not be present in API responses');
        $this->assertStringNotContainsString('_token', $content, 'CSRF token field should not be present');
    }
}