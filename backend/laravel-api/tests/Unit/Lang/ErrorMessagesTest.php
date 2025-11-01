<?php

use Illuminate\Support\Facades\Lang;

describe('Error Messages Translation', function () {
    beforeEach(function () {
        // エラーコード定義を読み込み
        $errorCodesPath = base_path('../../shared/error-codes.json');
        if (! file_exists($errorCodesPath)) {
            $this->markTestSkipped('Error codes definition file not found');
        }

        $this->errorCodes = json_decode(file_get_contents($errorCodesPath), true);
    });

    it('has Japanese translation for all error codes', function () {
        Lang::setLocale('ja');

        foreach ($this->errorCodes as $code => $definition) {
            $translationKey = $definition['translation_key'];

            expect(Lang::has($translationKey, 'ja'))
                ->toBeTrue("Translation key '{$translationKey}' not found for error code '{$code}'");

            $message = trans($translationKey);
            expect($message)
                ->not->toBeEmpty("Translation message is empty for '{$translationKey}'")
                ->and($message)->not->toBe($translationKey);
        }
    });

    it('has English translation for all error codes', function () {
        Lang::setLocale('en');

        foreach ($this->errorCodes as $code => $definition) {
            $translationKey = $definition['translation_key'];

            expect(Lang::has($translationKey, 'en'))
                ->toBeTrue("Translation key '{$translationKey}' not found for error code '{$code}'");

            $message = trans($translationKey);
            expect($message)
                ->not->toBeEmpty("Translation message is empty for '{$translationKey}'")
                ->and($message)->not->toBe($translationKey);
        }
    });

    it('returns default message when translation key not found', function () {
        Lang::setLocale('ja');

        $nonExistentKey = 'errors.nonexistent.key';
        $message = trans($nonExistentKey);

        // Laravelは存在しないキーの場合、キー自体を返す
        expect($message)->toBe($nonExistentKey);
    });

    it('falls back to English when Japanese translation not found', function () {
        // デフォルトロケールを英語に設定
        config(['app.fallback_locale' => 'en']);
        Lang::setLocale('fr'); // 存在しないロケールを設定

        // 英語のメッセージにフォールバックすることを確認
        $message = trans('errors.auth.invalid_credentials');
        expect($message)->toBe('Invalid email or password');
    });

    it('translation keys match error code definitions', function () {
        $translationKeys = [];

        // 日本語翻訳ファイルから全キーを収集
        $jaErrors = require base_path('lang/ja/errors.php');
        foreach ($jaErrors as $category => $messages) {
            foreach ($messages as $key => $message) {
                $translationKeys[] = "errors.{$category}.{$key}";
            }
        }

        // エラーコード定義の全translation_keyが翻訳ファイルに存在することを確認
        foreach ($this->errorCodes as $code => $definition) {
            $translationKey = $definition['translation_key'];
            expect(in_array($translationKey, $translationKeys, true))
                ->toBeTrue("Translation key '{$translationKey}' not found in lang files for error code '{$code}'");
        }
    });
});
