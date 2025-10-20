<?php

declare(strict_types=1);

/**
 * .env.exampleファイルのテスト
 *
 * Requirements: 13.6
 */
describe('EnvExample', function () {
    beforeEach(function () {
        $this->envExamplePath = base_path('.env.example');
        $this->envExampleContent = file_get_contents($this->envExamplePath);
    });

    it('.env.exampleファイルが存在すること', function () {
        expect(file_exists($this->envExamplePath))->toBeTrue();
    });

    it('CACHE_HEADERS_ENABLED環境変数が記載されていること', function () {
        expect($this->envExampleContent)->toContain('CACHE_HEADERS_ENABLED');
    });
});
