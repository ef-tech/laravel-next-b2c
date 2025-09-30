<?php

declare(strict_types=1);

/**
 * resources/views/ ディレクトリが完全削除されていることをテスト
 */
it('views_directory_is_completely_removed', function () {
    $viewsPath = resource_path('views');
    expect($viewsPath)->not->toBeDirectory('Views directory should be completely removed for API-only architecture');
});

/**
 * Bladeテンプレート関連機能が除去されていることをテスト
 */
it('blade_template_functionality_is_removed', function () {
    // welcome.blade.phpが存在しないことを確認
    $welcomeViewPath = resource_path('views/welcome.blade.php');
    expect($welcomeViewPath)->not->toBeFile('Welcome Blade template should not exist');

    // viewsディレクトリ内の全ファイルが削除されていることを確認
    $viewsPath = resource_path('views');
    if (is_dir($viewsPath)) {
        $files = scandir($viewsPath);
        $actualFiles = array_diff($files, ['.', '..']);
        expect($actualFiles)->toBeEmpty('Views directory should be empty or not exist');
    }
});

/**
 * Web関連ミドルウェアスタックが削除されていることをテスト
 */
it('web_middleware_stack_is_removed', function () {
    $bootstrapPath = base_path('bootstrap/app.php');
    $bootstrapContent = file_get_contents($bootstrapPath);

    // セッション関連ミドルウェアが除外されていることを確認
    expect($bootstrapContent)->toContain('StartSession::class', 'StartSession middleware should be in remove list');
    expect($bootstrapContent)->toContain('EncryptCookies::class', 'EncryptCookies middleware should be in remove list');
    expect($bootstrapContent)->toContain('VerifyCsrfToken::class', 'VerifyCsrfToken middleware should be in remove list');

    // middleware->remove() が呼ばれていることを確認
    expect($bootstrapContent)->toContain('$middleware->remove([', 'Middleware removal should be configured');
});

/**
 * CSRF攻撃対象の完全除去確認
 */
it('csrf_attack_surface_is_completely_removed', function () {
    // ヘルスチェックエンドポイントにCSRFトークンが含まれていないことを確認
    $response = $this->get('/up');
    $response->assertStatus(200);

    $content = $response->getContent();

    // CSRFトークン関連要素が含まれていないことを確認
    expect($content)->not->toContain('csrf-token', 'CSRF token should not be present');
    expect($content)->not->toContain('_token', 'CSRF token field should not be present');
    expect($content)->not->toContain('@csrf', 'Blade CSRF directive should not be present');

    // meta要素にCSRFトークンが含まれていないことを確認
    expect($content)->not->toContain('<meta name="csrf-token"', 'CSRF meta tag should not be present');
});

/**
 * API専用アーキテクチャでのレスポンス確認
 */
it('api_only_architecture_responses', function () {
    // ヘルスチェックはHTMLレスポンス（特殊なケース）
    $healthResponse = $this->get('/up');
    $healthResponse->assertStatus(200);
    expect($healthResponse->headers->get('content-type'))->toContain('text/html');

    // API エンドポイントはJSONレスポンス
    $apiResponse = $this->getJson('/api/user');
    $apiResponse->assertStatus(401); // 認証エラー
    expect($apiResponse->headers->get('content-type'))->toContain('application/json');
});

/**
 * ビューレンダリング機能の完全除去確認
 */
it('view_rendering_functionality_is_completely_removed', function () {
    // ビューヘルパー関数がもはや使用されないことをテスト
    // routes/web.phpが削除されているため、view()関数を使用するルートが存在しないことを確認

    $routeFiles = [
        base_path('routes/api.php'),
        base_path('routes/console.php'),
    ];

    foreach ($routeFiles as $routeFile) {
        if (file_exists($routeFile)) {
            $content = file_get_contents($routeFile);
            // view()関数が使用されていないことを確認（API専用のため）
            expect($content)->not->toContain(
                'return view(',
                'View function should not be used in API-only routes: '.basename($routeFile)
            );
        }
    }
});
