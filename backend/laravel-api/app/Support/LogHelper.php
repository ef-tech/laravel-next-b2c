<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ログ出力用ヘルパークラス
 *
 * 個人情報のハッシュ化機能を提供
 */
final class LogHelper
{
    /**
     * 個人情報をハッシュ化するかどうかを判定
     */
    private static function shouldHashSensitiveData(): bool
    {
        return (bool) config('logging.hash_sensitive_data', false);
    }

    /**
     * 個人情報（ユーザーID、メールアドレスなど）をハッシュ化する
     *
     * LOG_HASH_SENSITIVE_DATA=true の場合、SHA-256ハッシュ化
     * LOG_HASH_SENSITIVE_DATA=false の場合、そのまま返す
     *
     * @param  mixed  $value  ハッシュ化対象の値
     * @return mixed ハッシュ化後の値（nullの場合はnull）
     */
    public static function hashSensitiveData(mixed $value): mixed
    {
        // nullの場合はそのまま返す
        if ($value === null) {
            return null;
        }

        // ハッシュ化が無効の場合はそのまま返す
        if (! self::shouldHashSensitiveData()) {
            return $value;
        }

        // 配列の場合は各要素をハッシュ化
        if (is_array($value)) {
            return array_map(fn ($item) => self::hashSensitiveData($item), $value);
        }

        // オブジェクトの場合は文字列に変換してからハッシュ化
        // __toString()メソッドを持つオブジェクトのみ対応
        if (is_object($value)) {
            if (! method_exists($value, '__toString')) {
                return hash('sha256', get_class($value));
            }

            $value = (string) $value;
        }

        // スカラー値をSHA-256でハッシュ化
        return hash('sha256', (string) $value);
    }
}
