<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIバージョニングミドルウェア
 *
 * リクエストURLまたはヘッダーからAPIバージョン番号を抽出し、
 * リクエスト属性に保存。レスポンスヘッダーにバージョン情報を付与します。
 */
class ApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // URLからバージョン番号を抽出（正規表現: /api/v1/, /api/v2/ 等）
        $path = $request->path();
        $version = $this->extractVersionFromUrl($path);

        // バージョンが見つからない場合、ヘッダーから取得を試みる
        if ($version === null) {
            $version = $request->header('X-API-Version');
        }

        // それでも見つからない場合、デフォルトバージョンを適用
        if ($version === null) {
            $version = config('api.default_version', 'v1');
        }

        // サポート対象バージョンかチェック
        $supportedVersions = config('api.supported_versions', ['v1']);
        if (! in_array($version, $supportedVersions, true)) {
            return response()->json([
                'message' => 'Version not supported',
                'supported_versions' => $supportedVersions,
            ], 404, ['X-API-Version' => $version]);
        }

        // リクエスト属性にバージョン情報を保存
        $request->attributes->set('api_version', $version);

        // 次のミドルウェアへ
        $response = $next($request);

        // レスポンスヘッダーにバージョン情報を付与
        $response->headers->set('X-API-Version', $version);

        return $response;
    }

    /**
     * URLからバージョン番号を抽出
     */
    private function extractVersionFromUrl(string $path): ?string
    {
        // 正規表現で /api/v{数字}/ パターンを検索
        if (preg_match('/^api\/v(\d+)(?:\/|$)/', $path, $matches)) {
            return 'v'.$matches[1];
        }

        return null;
    }
}
