/**
 * APIエラーレスポンス型定義（Laravel統一エラーレスポンス形式）
 */
export interface ApiErrorResponse {
  code: string;
  message: string;
  errors?: Record<string, string[]>;
  trace_id?: string;
}

/**
 * APIエラークラス
 *
 * バックエンド（Laravel）から返される統一エラーレスポンスをラップし、
 * フロントエンドで扱いやすい形式に変換する。
 */
export class ApiError extends Error {
  constructor(
    public readonly code: string,
    message: string,
    public readonly statusCode: number,
    public readonly traceId?: string,
    public readonly errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = "ApiError";

    // Set prototype explicitly for instanceof checks
    Object.setPrototypeOf(this, ApiError.prototype);
  }
}

/**
 * APIエラーハンドラー
 *
 * Responseオブジェクトからエラー情報を抽出し、ApiErrorをスローする。
 * trace_idをログに記録して、デバッグを容易にする。
 *
 * @param response - Fetch APIのResponseオブジェクト
 * @throws {ApiError} 統一エラー形式のApiError
 */
export async function handleApiError(response: Response): Promise<never> {
  let errorData: ApiErrorResponse;

  try {
    errorData = await response.json();
  } catch {
    // JSON解析失敗時のフォールバック
    errorData = {
      code: "UNKNOWN_ERROR",
      message: `HTTP ${response.status}: ${response.statusText}`,
    };
  }

  const { code, message, errors, trace_id } = errorData;

  // trace_idをログに記録（デバッグ用）
  if (trace_id) {
    console.error(`API Error [${code}] trace_id: ${trace_id} - ${message}`);
  } else {
    console.error(`API Error [${code}] - ${message}`);
  }

  // フィールド別エラーもログに記録
  if (errors) {
    console.error("Validation errors:", errors);
  }

  throw new ApiError(code, message, response.status, trace_id, errors);
}
