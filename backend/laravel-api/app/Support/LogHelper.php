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
        if (is_object($value)) {
            $value = (string) $value;
        }

        // スカラー値をSHA-256でハッシュ化
        return hash('sha256', (string) $value);
    }

    /**
     * ユーザーIDをハッシュ化する
     *
     * @param  int|string|null  $userId  ユーザーID
     * @return int|string|null ハッシュ化後のユーザーID
     */
    public static function hashUserId(int|string|null $userId): int|string|null
    {
        return self::hashSensitiveData($userId);
    }

    /**
     * メールアドレスをハッシュ化する
     *
     * @param  string|null  $email  メールアドレス
     * @return string|null ハッシュ化後のメールアドレス
     */
    public static function hashEmail(?string $email): ?string
    {
        return self::hashSensitiveData($email);
    }

    /**
     * IPアドレスをハッシュ化する
     *
     * @param  string|null  $ip  IPアドレス
     * @return string|null ハッシュ化後のIPアドレス
     */
    public static function hashIpAddress(?string $ip): ?string
    {
        return self::hashSensitiveData($ip);
    }
}
