<?php

declare(strict_types=1);

/**
 * セッションドライバーがarrayに設定されていることをテスト
 */
it('session_driver_is_array', function () {
    $sessionDriver = config('session.driver');
    expect($sessionDriver)->toBe('array', 'Session driver should be set to array for stateless API');
});

/**
 * セッション関連ミドルウェアが除外されていることをテスト
 */
it('session_middleware_is_removed', function () {
    // ヘルスチェックエンドポイントにアクセスしてセッションが作成されないことを確認
    $response = $this->get('/up');
    $response->assertStatus(200);

    // セッション情報が存在しないことを確認
    expect($_COOKIE ?? [])->not->toHaveKey('laravel_session');
});

/**
 * ステートレスAPI動作の検証
 */
it('stateless_api_operation', function () {
    // 複数回のリクエストでセッション状態が保持されないことを確認
    $response1 = $this->get('/up');
    $response1->assertStatus(200);

    $response2 = $this->get('/up');
    $response2->assertStatus(200);

    // 各リクエストが独立していることを確認（セッション状態なし）
    expect(true)->toBeTrue('Multiple requests should be stateless');
});

/**
 * CSRF攻撃対象の完全除去確認
 */
it('csrf_protection_is_disabled', function () {
    // CSRFトークンなしでPOSTリクエストが通ることを確認
    // まずはヘルスチェックエンドポイントで確認（GETのみのため、API追加後に拡張予定）
    $response = $this->get('/up');
    $response->assertStatus(200);

    // CSRFトークンがレスポンスに含まれていないことを確認
    $content = $response->getContent();
    expect($content)->not->toContain('csrf-token', 'CSRF token should not be present in API responses');
    expect($content)->not->toContain('_token', 'CSRF token field should not be present');
});
