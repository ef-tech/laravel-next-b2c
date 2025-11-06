<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Error Messages (Japanese)
    |--------------------------------------------------------------------------
    |
    | RFC 7807準拠のエラーメッセージ（日本語）
    | エラーコード定義（shared/error-codes.json）と対応
    |
    */

    'auth' => [
        'invalid_credentials' => 'メールアドレスまたはパスワードが正しくありません',
        'token_expired' => '認証トークンの有効期限が切れています',
        'token_invalid' => '認証トークンが無効です',
        'insufficient_permissions' => 'この操作を実行する権限がありません',
    ],

    'validation' => [
        'invalid_input' => '入力内容にエラーがあります',
        'invalid_email' => 'メールアドレスの形式が正しくありません',
    ],

    'business' => [
        'resource_not_found' => '指定されたリソースが見つかりません',
        'resource_conflict' => 'すでに同じリソースが存在します',
    ],

    'infrastructure' => [
        'database_unavailable' => 'データベースに接続できません',
        'external_api_error' => '外部サービスとの通信に失敗しました',
        'request_timeout' => '処理に時間がかかりすぎました',
    ],
];
