<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * V1 CSPレポートリクエスト
 *
 * CSP違反レポートAPIのバリデーションルールを定義します。
 */
final class CspReportRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * application/csp-report Content-Typeの場合、Laravelが自動的にJSONデコードしないため、
     * 明示的にJSONボディからデータを取得してリクエストにマージします。
     */
    protected function prepareForValidation(): void
    {
        // $this->input()でデータが取得できない場合（application/csp-report）、
        // $this->json()から取得してマージ
        if (! $this->has('csp-report') && $this->json('csp-report') !== null) {
            $this->merge([
                'csp-report' => $this->json('csp-report'),
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'csp-report' => ['required', 'array', 'min:1'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csp-report.required' => 'The CSP report is required.',
            'csp-report.array' => 'The CSP report must be an array.',
            'csp-report.min' => 'The CSP report must not be empty.',
        ];
    }
}
