<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * セキュリティヘッダーをレスポンスに付与
     *
     * @param  Request  $request  リクエストオブジェクト
     * @param  Closure  $next  次のミドルウェア
     * @return Response レスポンス（ヘッダー付与済み）
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // 基本セキュリティヘッダーを付与
        $response = $this->addBasicHeaders($response);

        // CSP ヘッダーを付与（有効な場合）
        $response = $this->addCspHeaders($response, $request);

        // HSTS ヘッダーを付与（HTTPS 環境かつ有効な場合）
        $response = $this->addHstsHeader($response, $request);

        return $response;
    }

    /**
     * 基本セキュリティヘッダーを付与
     *
     * @param  Response  $response  レスポンスオブジェクト
     * @return Response ヘッダー付与後のレスポンス
     */
    private function addBasicHeaders(Response $response): Response
    {
        // X-Frame-Options
        $response->headers->set(
            'X-Frame-Options',
            (string) config('security.x_frame_options', 'DENY')
        );

        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer-Policy
        $response->headers->set(
            'Referrer-Policy',
            (string) config('security.referrer_policy', 'strict-origin-when-cross-origin')
        );

        return $response;
    }

    /**
     * CSP ヘッダーを付与
     *
     * @param  Response  $response  レスポンスオブジェクト
     * @param  Request  $request  リクエストオブジェクト
     * @return Response ヘッダー付与後のレスポンス
     */
    private function addCspHeaders(Response $response, Request $request): Response
    {
        /** @var bool $cspEnabled */
        $cspEnabled = config('security.csp.enabled', false);

        if (! $cspEnabled) {
            return $response;
        }

        /** @var array<string, mixed> $cspConfig */
        $cspConfig = config('security.csp', []);

        // CSP ポリシー文字列を構築
        $cspPolicy = $this->buildCspPolicy($cspConfig);

        // CSP モードに応じてヘッダー名を決定
        /** @var string $cspMode */
        $cspMode = $cspConfig['mode'] ?? 'report-only';
        $headerName = $cspMode === 'enforce'
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        $response->headers->set($headerName, $cspPolicy);

        return $response;
    }

    /**
     * CSP ポリシー文字列を構築
     *
     * @param  array<string, mixed>  $config  CSP 設定配列
     * @return string CSP ポリシー文字列
     */
    private function buildCspPolicy(array $config): string
    {
        /** @var array<string, array<string>> $directives */
        $directives = $config['directives'] ?? [];

        $policyParts = [];

        foreach ($directives as $directive => $values) {
            if (empty($values)) {
                continue;
            }

            // CSP値を正規化: 'self', 'none'などのキーワードにシングルクォートを追加
            $normalizedValues = array_map(function ($value) {
                // 既にシングルクォートで囲まれている場合はそのまま
                if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    return $value;
                }

                // CSPキーワード（self, none, unsafe-inline等）にシングルクォートを追加
                $keywords = ['self', 'none', 'unsafe-inline', 'unsafe-eval', 'strict-dynamic', 'report-sample'];
                if (in_array($value, $keywords, true)) {
                    return "'{$value}'";
                }

                // それ以外（data:, https:, URLなど）はそのまま
                return $value;
            }, $values);

            $directiveString = $directive.' '.implode(' ', $normalizedValues);
            $policyParts[] = $directiveString;
        }

        // report-uri ディレクティブを追加（設定されている場合）
        if (! empty($config['report_uri'])) {
            /** @var string $reportUri */
            $reportUri = $config['report_uri'];
            $policyParts[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $policyParts);
    }

    /**
     * HSTS ヘッダーを付与
     *
     * @param  Response  $response  レスポンスオブジェクト
     * @param  Request  $request  リクエストオブジェクト
     * @return Response ヘッダー付与後のレスポンス
     */
    private function addHstsHeader(Response $response, Request $request): Response
    {
        /** @var bool $hstsEnabled */
        $hstsEnabled = config('security.hsts.enabled', false);

        // HSTS が無効の場合はスキップ
        if (! $hstsEnabled) {
            return $response;
        }

        // HTTPS 環境チェック（X-Forwarded-Proto ヘッダーも考慮）
        $isHttps = $request->secure()
            || $request->header('X-Forwarded-Proto') === 'https'
            || $request->server('HTTPS') === 'on';

        if (! $isHttps) {
            return $response;
        }

        /** @var int $maxAge */
        $maxAge = config('security.hsts.max_age', 31536000);
        /** @var bool $includeSubdomains */
        $includeSubdomains = config('security.hsts.include_subdomains', true);
        /** @var bool $preload */
        $preload = config('security.hsts.preload', true);

        $hstsValue = sprintf('max-age=%d', $maxAge);

        if ($includeSubdomains) {
            $hstsValue .= '; includeSubDomains';
        }

        if ($preload) {
            $hstsValue .= '; preload';
        }

        $response->headers->set('Strict-Transport-Security', $hstsValue);

        return $response;
    }
}
