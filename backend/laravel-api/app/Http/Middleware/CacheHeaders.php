<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CacheHeaders Middleware
 *
 * GETリクエストに対してCache-ControlとExpiresヘッダーを設定し、
 * HTTPキャッシングを最適化します。
 * - 開発環境ではno-cache設定
 * - 本番環境ではエンドポイントごとのmax-age設定
 * - Expiresヘッダー併用でブラウザキャッシュ最適化
 * - POST/PUT/PATCH/DELETEリクエストはスキップ
 * - 環境変数による機能有効/無効制御
 *
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7
 */
final class CacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 環境変数でキャッシュヘッダー機能が無効化されている場合はスキップ
        if (! config('cache_headers.enabled', false)) {
            // 既存のキャッシュヘッダーを削除
            $response->headers->remove('Cache-Control');
            $response->headers->remove('Expires');

            return $response;
        }

        // GETリクエストのみ処理（POST/PUT/PATCH/DELETEはスキップ）
        if ($request->method() !== 'GET') {
            // データ変更操作ではキャッシュヘッダーを削除
            $response->headers->remove('Cache-Control');
            $response->headers->remove('Expires');

            return $response;
        }

        // 開発環境ではno-cache設定
        if (config('app.env') === 'local' || config('app.env') === 'testing') {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

            return $response;
        }

        // 本番環境：エンドポイントごとのmax-age設定
        $path = $request->path();
        $ttlByPath = config('cache_headers.ttl_by_path', []);
        $defaultTtl = config('cache_headers.default_ttl', 300);

        // エンドポイント固有のTTLを取得（なければデフォルト使用）
        $ttl = $ttlByPath[$path] ?? $defaultTtl;

        // Cache-Controlヘッダー設定
        $response->headers->set('Cache-Control', sprintf('public, max-age=%d', $ttl));

        // Expiresヘッダー設定（Cache-Controlと併用）
        $expiresTime = now()->addSeconds($ttl);
        $response->headers->set('Expires', $expiresTime->format('D, d M Y H:i:s').' GMT');

        return $response;
    }
}
