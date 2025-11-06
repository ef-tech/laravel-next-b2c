<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromAcceptLanguage
{
    /**
     * Accept-Languageヘッダーを解析し、ロケールを設定する
     */
    public function handle(Request $request, Closure $next): Response
    {
        $acceptLanguage = $request->header('Accept-Language');
        $locale = $this->parseAcceptLanguage($acceptLanguage);

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Accept-Languageヘッダーを解析してサポートされているロケールを返す
     *
     * @param  string|null  $acceptLanguage  Accept-Languageヘッダーの値（例: "ja", "en-US", "fr,en;q=0.9,ja;q=0.8"）
     * @return string サポートされているロケール（ja/en）またはデフォルトロケール（ja）
     */
    protected function parseAcceptLanguage(?string $acceptLanguage): string
    {
        // Accept-Languageヘッダーがnullまたは空文字列の場合はデフォルトロケールを返す
        if (! $acceptLanguage || trim($acceptLanguage) === '') {
            return $this->getDefaultLocale();
        }

        // Accept-Languageヘッダーをパースする（例: "fr,en;q=0.9,ja;q=0.8"）
        $languages = $this->parseLanguages($acceptLanguage);

        // サポートされている言語を探す
        foreach ($languages as $language) {
            // 地域コード（ja-JP）を言語コード（ja）に変換
            $languageCode = strtolower(explode('-', $language)[0]);

            if (in_array($languageCode, $this->getSupportedLocales(), true)) {
                return $languageCode;
            }
        }

        return $this->getDefaultLocale();
    }

    /**
     * Accept-Languageヘッダーをパースして言語のリストを返す
     *
     * @return array<int, string>
     */
    protected function parseLanguages(string $acceptLanguage): array
    {
        $languages = [];

        // カンマで分割
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            // セミコロンで分割（quality値を除去）
            $langPart = explode(';', trim($part))[0];
            $languages[] = trim($langPart);
        }

        return $languages;
    }

    /**
     * サポートされているロケールのリストを取得する
     *
     * @return array<int, string>
     */
    protected function getSupportedLocales(): array
    {
        return ['ja', 'en'];
    }

    /**
     * デフォルトロケールを取得する
     */
    protected function getDefaultLocale(): string
    {
        return 'ja';
    }
}
