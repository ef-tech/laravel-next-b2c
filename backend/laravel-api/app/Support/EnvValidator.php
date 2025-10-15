<?php

declare(strict_types=1);

namespace App\Support;

class EnvValidator
{
    /** @var array<string, array<string, mixed>> */
    private array $schema;

    /** @var array<string, string> */
    private array $env;

    private string $mode;

    /**
     * @param  array<string, array<string, mixed>>  $schema
     * @param  array<string, string>  $env
     * @param  string  $mode  'error' or 'warning'
     */
    public function __construct(array $schema, array $env, string $mode = 'error')
    {
        $this->schema = $schema;
        $this->env = $env;
        $this->mode = $mode;
    }

    /**
     * 環境変数をバリデーション
     *
     * @return array{valid: bool, errors: array<string, string>}
     */
    public function validate(): array
    {
        $errors = [];

        foreach ($this->schema as $key => $config) {
            // 必須チェック
            if ($this->isRequired($key, $config)) {
                if (! $this->hasValue($key)) {
                    $errors[$key] = $this->formatRequiredError($key, $config);

                    continue;
                }
            }

            // 値が存在する場合のみ、型チェックと許可値チェック
            if ($this->hasValue($key)) {
                // 型チェック
                if (isset($config['type'])) {
                    if (! $this->validateType($key, $config['type'])) {
                        $errors[$key] = $this->formatTypeError($key, $config);

                        continue;
                    }
                }

                // 許可値チェック
                if (isset($config['allowed_values'])) {
                    if (! $this->validateAllowedValues($key, $config['allowed_values'])) {
                        $errors[$key] = $this->formatAllowedValuesError($key, $config);

                        continue;
                    }
                }
            }
        }

        // 警告モードの場合、エラーがあってもvalid=trueを返す
        if ($this->mode === 'warning') {
            return [
                'valid' => true,
                'errors' => $errors,
            ];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 環境変数が必須かどうか判定
     */
    private function isRequired(string $key, array $config): bool
    {
        // 基本的な必須チェック
        if ($config['required'] ?? false) {
            return true;
        }

        // 条件付き必須チェック
        if (isset($config['required_if'])) {
            foreach ($config['required_if'] as $dependentKey => $dependentValues) {
                if ($this->hasValue($dependentKey)) {
                    $currentValue = $this->env[$dependentKey];
                    if (in_array($currentValue, $dependentValues, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 環境変数が設定されているかチェック
     */
    private function hasValue(string $key): bool
    {
        return isset($this->env[$key]) && $this->env[$key] !== '';
    }

    /**
     * 型バリデーション
     */
    private function validateType(string $key, string $type): bool
    {
        $value = $this->env[$key];

        return match ($type) {
            'string' => true, // 文字列型はすべて許可
            'integer' => $this->isInteger($value),
            'boolean' => $this->isBoolean($value),
            'url' => $this->isUrl($value),
            'email' => $this->isEmail($value),
            default => true,
        };
    }

    /**
     * 許可値バリデーション
     *
     * @param  array<int, string>  $allowedValues
     */
    private function validateAllowedValues(string $key, array $allowedValues): bool
    {
        $value = $this->env[$key];

        return in_array($value, $allowedValues, true);
    }

    /**
     * 整数型チェック
     */
    private function isInteger(string $value): bool
    {
        return (string) (int) $value === $value;
    }

    /**
     * 真偽値型チェック
     */
    private function isBoolean(string $value): bool
    {
        $lowercaseValue = strtolower($value);

        return in_array($lowercaseValue, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'], true);
    }

    /**
     * URL型チェック
     */
    private function isUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Email型チェック
     */
    private function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 必須エラーメッセージフォーマット
     */
    private function formatRequiredError(string $key, array $config): string
    {
        $description = $config['description'] ?? '';
        $default = isset($config['default']) ? " (デフォルト: {$config['default']})" : '';

        return "環境変数 {$key} は必須です。{$description}{$default}";
    }

    /**
     * 型エラーメッセージフォーマット
     */
    private function formatTypeError(string $key, array $config): string
    {
        $type = $config['type'];
        $description = $config['description'] ?? '';

        return "環境変数 {$key} は {$type} 型である必要があります。{$description}";
    }

    /**
     * 許可値エラーメッセージフォーマット
     */
    private function formatAllowedValuesError(string $key, array $config): string
    {
        $allowedValues = implode(', ', $config['allowed_values']);
        $description = $config['description'] ?? '';

        return "環境変数 {$key} は次のいずれかの値である必要があります: {$allowedValues}。{$description}";
    }
}
