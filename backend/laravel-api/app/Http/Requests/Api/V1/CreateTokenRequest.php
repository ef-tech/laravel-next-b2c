<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * V1 トークン作成リクエスト
 */
final class CreateTokenRequest extends FormRequest
{
    /**
     * リクエストが認可されているか判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを定義
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
