<?php

declare(strict_types=1);

it('returns successful response for health check endpoint', function () {
    // API専用アーキテクチャでは、ヘルスチェックエンドポイントをテスト
    $response = $this->get('/up');

    $response->assertStatus(200);
});
