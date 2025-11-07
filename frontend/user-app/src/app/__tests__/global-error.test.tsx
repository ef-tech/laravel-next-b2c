/**
 * Global Error Boundary Component Tests (User App)
 *
 * Task 11: Global Error Boundary Component Testsの実装
 * - Task 11.1: ブラウザロケール検出テスト実装
 * - Task 11.2: Global Error Boundary両ロケールテスト実装
 * - Task 11.3: Global Error Boundary 90%以上カバレッジ達成
 *
 * Requirements:
 * - REQ-8.5: Global Error Boundary i18n tests with browser locale detection
 * - REQ-8.6: 90%+ code coverage for Global Error Boundary components
 */

import { render, screen, waitFor } from "@testing-library/react";
import GlobalError from "../global-error";
import { NetworkError } from "@/lib/network-error";
import { ApiError } from "@/lib/api-error";

describe("Global Error Boundary (User App)", () => {
  const mockReset = jest.fn();
  let originalLang: string;
  let originalNavigatorLanguages: readonly string[];

  beforeEach(() => {
    mockReset.mockClear();
    // Suppress console.error for cleaner test output
    jest.spyOn(console, "error").mockImplementation(() => {});

    // Save original values
    originalLang = document.documentElement.lang;
    originalNavigatorLanguages = navigator.languages;
  });

  afterEach(() => {
    jest.restoreAllMocks();

    // Restore original document.documentElement.lang
    Object.defineProperty(document.documentElement, "lang", {
      writable: true,
      configurable: true,
      value: originalLang,
    });

    // Restore original navigator.languages
    Object.defineProperty(navigator, "languages", {
      writable: true,
      configurable: true,
      value: originalNavigatorLanguages,
    });
  });

  describe("Task 11.1: ブラウザロケール検出テスト", () => {
    it("document.documentElement.langが'ja'のとき、日本語メッセージを表示する", async () => {
      // Mock document.documentElement.lang
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      const error = new Error("Test error");
      render(<GlobalError error={error} reset={mockReset} />);

      // 日本語メッセージが表示されることを確認
      await waitFor(() => {
        expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();
      });
    });

    it("document.documentElement.langが'en'のとき、英語メッセージを表示する", async () => {
      // Mock document.documentElement.lang
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "en",
      });

      const error = new Error("Test error");
      render(<GlobalError error={error} reset={mockReset} />);

      // 英語メッセージが表示されることを確認
      await waitFor(() => {
        expect(screen.getByText("An unexpected error occurred")).toBeInTheDocument();
      });
    });

    it("navigator.languagesが['en-US']のとき、英語メッセージを表示する", async () => {
      // Reset document.documentElement.lang
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "",
      });

      // Mock navigator.languages
      Object.defineProperty(navigator, "languages", {
        writable: true,
        configurable: true,
        value: ["en-US"],
      });

      const error = new Error("Test error");
      render(<GlobalError error={error} reset={mockReset} />);

      // 英語メッセージが表示されることを確認
      await waitFor(() => {
        expect(screen.getByText("An unexpected error occurred")).toBeInTheDocument();
      });
    });

    it("navigator.languagesが['ja-JP']のとき、日本語メッセージを表示する", async () => {
      // Reset document.documentElement.lang
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "",
      });

      // Mock navigator.languages
      Object.defineProperty(navigator, "languages", {
        writable: true,
        configurable: true,
        value: ["ja-JP"],
      });

      const error = new Error("Test error");
      render(<GlobalError error={error} reset={mockReset} />);

      // 日本語メッセージが表示されることを確認
      await waitFor(() => {
        expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();
      });
    });

    it("document.documentElement.langもnavigator.languagesも該当なしの場合、デフォルト日本語にフォールバックする", async () => {
      // Reset document.documentElement.lang
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "",
      });

      // Mock navigator.languages with unsupported locale
      Object.defineProperty(navigator, "languages", {
        writable: true,
        configurable: true,
        value: ["fr-FR"],
      });

      const error = new Error("Test error");
      render(<GlobalError error={error} reset={mockReset} />);

      // デフォルト日本語メッセージが表示されることを確認
      await waitFor(() => {
        expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();
      });
    });
  });

  describe("Task 11.2: Global Error Boundary両ロケールテスト", () => {
    it("日本語ロケール時、すべての日本語メッセージが表示される（汎用エラー）", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      const error = new Error("Test error") as Error & { digest?: string };
      error.digest = "test-digest-123";

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();
        expect(screen.getByText("再試行")).toBeInTheDocument();
        expect(screen.getByText("Error ID:")).toBeInTheDocument();
        expect(screen.getByText("test-digest-123")).toBeInTheDocument();
        expect(screen.getByText("お問い合わせの際は、このIDをお伝えください")).toBeInTheDocument();
      });
    });

    it("英語ロケール時、すべての英語メッセージが表示される（汎用エラー）", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "en",
      });

      const error = new Error("Test error") as Error & { digest?: string };
      error.digest = "test-digest-456";

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(screen.getByText("An unexpected error occurred")).toBeInTheDocument();
        expect(screen.getByText("Retry")).toBeInTheDocument();
        expect(screen.getByText("Error ID:")).toBeInTheDocument();
        expect(screen.getByText("test-digest-456")).toBeInTheDocument();
        expect(
          screen.getByText("Please provide this ID when contacting support"),
        ).toBeInTheDocument();
      });
    });

    it("digestがある場合、Error ID表示エリアが表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      const error = new Error("Test error") as Error & { digest?: string };
      error.digest = "test-digest-789";

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(screen.getByText("Error ID:")).toBeInTheDocument();
        expect(screen.getByText("test-digest-789")).toBeInTheDocument();
      });
    });

    it("digestがない場合、Error ID表示エリアが表示されない", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      const error = new Error("Test error");

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(screen.queryByText("Error ID:")).not.toBeInTheDocument();
      });
    });

    it("html lang属性が日本語ロケール時に'ja'に設定される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      const error = new Error("Test error");

      render(<GlobalError error={error} reset={mockReset} />);

      // GlobalError componentは<html>要素を返すため、bodyの親要素でチェック
      await waitFor(() => {
        const bodyElement = document.body;
        const htmlElement = bodyElement.parentElement;
        expect(htmlElement).toHaveAttribute("lang", "ja");
      });
    });

    it("html lang属性が英語ロケール時に'en'に設定される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "en",
      });

      const error = new Error("Test error");

      render(<GlobalError error={error} reset={mockReset} />);

      // GlobalError componentは<html>要素を返すため、bodyの親要素でチェック
      await waitFor(() => {
        const bodyElement = document.body;
        const htmlElement = bodyElement.parentElement;
        expect(htmlElement).toHaveAttribute("lang", "en");
      });
    });
  });

  describe("Task 11.3: NetworkError と ApiError の多言語対応", () => {
    it("NetworkError（タイムアウト）が日本語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      // Create NetworkError using fromFetchError factory method
      const timeoutError = new Error("Request timeout");
      timeoutError.name = "AbortError";
      const error = NetworkError.fromFetchError(timeoutError);

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(screen.getByText("ネットワークエラー")).toBeInTheDocument();
        expect(screen.getByText("タイムアウト")).toBeInTheDocument();
        expect(
          screen.getByText(
            "リクエストがタイムアウトしました。しばらくしてから再度お試しください。",
          ),
        ).toBeInTheDocument();
      });
    });

    it("NetworkError（接続エラー）が日本語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      // Create NetworkError using fromFetchError factory method
      const connectionError = new TypeError("Failed to fetch");
      const error = NetworkError.fromFetchError(connectionError);

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(screen.getByText("接続エラー")).toBeInTheDocument();
      });
      expect(
        screen.getByText(
          "ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。",
        ),
      ).toBeInTheDocument();
      const networkErrorTexts = screen.getAllByText("ネットワークエラー");
      expect(networkErrorTexts.length).toBeGreaterThan(0);
    });

    it("NetworkError（不明なエラー）が日本語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      // Create generic NetworkError
      const unknownError = new Error("Unknown network error");
      const error = NetworkError.fromFetchError(unknownError);

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(
          screen.getByText("予期しないエラーが発生しました。しばらくしてから再度お試しください。"),
        ).toBeInTheDocument();
      });
      const networkErrorTexts = screen.getAllByText("ネットワークエラー");
      expect(networkErrorTexts.length).toBe(2);
    });

    it("NetworkError（タイムアウト）が英語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "en",
      });

      // Create NetworkError using fromFetchError factory method
      const timeoutError = new Error("Request timeout");
      timeoutError.name = "AbortError";
      const error = NetworkError.fromFetchError(timeoutError);

      render(<GlobalError error={error} reset={mockReset} />);

      await waitFor(() => {
        expect(screen.getByText("Network Error")).toBeInTheDocument();
        expect(screen.getByText("Timeout")).toBeInTheDocument();
        expect(
          screen.getByText("The request timed out. Please try again later."),
        ).toBeInTheDocument();
      });
    });

    it("ApiErrorが日本語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      const error = new ApiError({
        type: "https://api.example.com/errors/not_found",
        title: "Not Found",
        status: 404,
        detail: "The requested resource was not found",
        error_code: "not_found",
        trace_id: "test-trace-id-123",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
      });

      render(<GlobalError error={error} reset={mockReset} />);

      // Check Japanese messages are displayed
      expect(screen.getByText("Not Found")).toBeInTheDocument();
      expect(screen.getByText(/ステータスコード.*404/)).toBeInTheDocument();
      expect(screen.getByText(/Request ID/)).toBeInTheDocument();
      expect(screen.getByText("test-trace-id-123")).toBeInTheDocument();
    });

    it("ApiErrorが英語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "en",
      });

      const error = new ApiError({
        type: "https://api.example.com/errors/internal_server_error",
        title: "Internal Server Error",
        status: 500,
        detail: "An error occurred",
        error_code: "internal_server_error",
        trace_id: "test-trace-id-456",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
      });

      render(<GlobalError error={error} reset={mockReset} />);

      // Check English messages are displayed
      expect(screen.getByText("Internal Server Error")).toBeInTheDocument();
      expect(screen.getByText(/Status Code.*500/)).toBeInTheDocument();
      expect(screen.getByText(/Request ID/)).toBeInTheDocument();
      expect(screen.getByText("test-trace-id-456")).toBeInTheDocument();
    });

    it("ApiErrorでvalidationErrorsがある場合、バリデーションエラーが日本語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      const error = new ApiError({
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "Validation failed",
        error_code: "validation_error",
        trace_id: "test-trace-id-validation",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
        errors: {
          email: ["メールアドレスの形式が正しくありません"],
          password: [
            "パスワードは8文字以上である必要があります",
            "パスワードに数字を含めてください",
          ],
        },
      });

      render(<GlobalError error={error} reset={mockReset} />);

      // Check validation errors are displayed
      expect(screen.getByText("Validation Error")).toBeInTheDocument();
      expect(screen.getByText(/入力エラー:/)).toBeInTheDocument();
      expect(screen.getByText(/email:/)).toBeInTheDocument();
      expect(screen.getAllByText(/メールアドレスの形式が正しくありません/).length).toBeGreaterThan(
        0,
      );
      expect(screen.getByText(/password:/)).toBeInTheDocument();
      expect(
        screen.getAllByText(
          /パスワードは8文字以上である必要があります, パスワードに数字を含めてください/,
        ).length,
      ).toBeGreaterThan(0);
    });

    it("ApiErrorでvalidationErrorsがある場合、バリデーションエラーが英語で表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "en",
      });

      const error = new ApiError({
        type: "https://api.example.com/errors/validation_error",
        title: "Validation Error",
        status: 422,
        detail: "Validation failed",
        error_code: "validation_error",
        trace_id: "test-trace-id-validation-en",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
        errors: {
          email: ["The email format is invalid"],
          password: ["Password must be at least 8 characters", "Password must contain numbers"],
        },
      });

      render(<GlobalError error={error} reset={mockReset} />);

      // Check validation errors are displayed in English
      expect(screen.getByText("Validation Error")).toBeInTheDocument();
      expect(screen.getByText(/Validation Errors:/)).toBeInTheDocument();
      expect(screen.getByText(/email:/)).toBeInTheDocument();
      expect(screen.getAllByText(/The email format is invalid/).length).toBeGreaterThan(0);
      expect(screen.getByText(/password:/)).toBeInTheDocument();
      expect(
        screen.getAllByText(/Password must be at least 8 characters, Password must contain numbers/)
          .length,
      ).toBeGreaterThan(0);
    });

    it("ApiError（cause経由で再構築）が正しく表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      // Create ApiError with cause (simulating Jest module resolution issue)
      const rfc7807Problem = {
        type: "https://api.example.com/errors/server_error",
        title: "Server Error",
        status: 500,
        detail: "Internal server error occurred",
        error_code: "server_error",
        trace_id: "test-trace-id-cause",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
      };

      const error = new Error("ApiError") as Error & { cause?: unknown };
      error.name = "ApiError";
      error.cause = rfc7807Problem;

      render(<GlobalError error={error} reset={mockReset} />);

      // Check ApiError reconstructed from cause is displayed correctly
      expect(screen.getByText("Server Error")).toBeInTheDocument();
      expect(screen.getByText(/ステータスコード.*500/)).toBeInTheDocument();
      expect(screen.getByText(/Request ID/)).toBeInTheDocument();
      expect(screen.getByText("test-trace-id-cause")).toBeInTheDocument();
    });

    it("Generic error（RFC7807 cause経由）が正しく表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "en",
      });

      // Create generic error with RFC7807 data in cause
      const rfc7807Problem = {
        type: "https://api.example.com/errors/bad_request",
        title: "Bad Request",
        status: 400,
        detail: "Invalid request parameters",
        error_code: "bad_request",
        trace_id: "test-trace-id-generic",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
      };

      const error = new Error("Generic error") as Error & { cause?: unknown };
      error.cause = rfc7807Problem;

      render(<GlobalError error={error} reset={mockReset} />);

      // Check ApiError reconstructed from generic cause is displayed correctly
      expect(screen.getByText("Bad Request")).toBeInTheDocument();
      expect(screen.getByText(/Status Code.*400/)).toBeInTheDocument();
      expect(screen.getByText(/Request ID/)).toBeInTheDocument();
      expect(screen.getByText("test-trace-id-generic")).toBeInTheDocument();
    });

    it("ApiError（プロパティ消失、cause経由で再構築）が正しく表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      // Create ApiError instance with properties lost (simulating serialization issue)
      const rfc7807Problem = {
        type: "https://api.example.com/errors/service_unavailable",
        title: "Service Unavailable",
        status: 503,
        detail: "Service temporarily unavailable",
        error_code: "service_unavailable",
        trace_id: "test-trace-id-lost-props",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
      };

      const error = new ApiError(rfc7807Problem);
      // Simulate property loss (Next.js Error Boundary serialization)
      Object.defineProperty(error, "title", { value: undefined, writable: true });
      Object.defineProperty(error, "status", { value: undefined, writable: true });

      render(<GlobalError error={error} reset={mockReset} />);

      // Check ApiError reconstructed from cause is displayed correctly
      expect(screen.getByText("Service Unavailable")).toBeInTheDocument();
      expect(screen.getByText(/ステータスコード.*503/)).toBeInTheDocument();
      expect(screen.getByText(/Request ID/)).toBeInTheDocument();
      expect(screen.getByText("test-trace-id-lost-props")).toBeInTheDocument();
    });

    it("ApiError（プロパティ消失、causeなし）がフォールバックで表示される", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      // Create ApiError instance with properties lost and no cause
      const rfc7807Problem = {
        type: "https://api.example.com/errors/unknown",
        title: "Unknown Error",
        status: 500,
        detail: "An unknown error occurred",
        error_code: "unknown",
        trace_id: "test-trace-id-no-cause",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
      };

      const error = new ApiError(rfc7807Problem);
      // Simulate property loss without cause
      Object.defineProperty(error, "title", { value: undefined, writable: true });
      Object.defineProperty(error, "status", { value: undefined, writable: true });
      Object.defineProperty(error, "cause", { value: undefined, writable: true });

      render(<GlobalError error={error} reset={mockReset} />);

      // ApiError instance without cause is still treated as ApiError (uses original instance as-is)
      // Request ID should still be displayed even with undefined title/status
      expect(screen.getByText("test-trace-id-no-cause")).toBeInTheDocument();
      expect(screen.getByText("再試行")).toBeInTheDocument();
    });

    it("ApiError再構築時にエラーが発生した場合、console.errorが呼ばれる", async () => {
      Object.defineProperty(document.documentElement, "lang", {
        writable: true,
        configurable: true,
        value: "ja",
      });

      // Create ApiError with cause that throws when accessed
      const rfc7807Problem = {
        type: "https://api.example.com/errors/test",
        title: "Test Error",
        status: 500,
        detail: "Test error detail",
        error_code: "test_error",
        trace_id: "test-trace-id-error",
        instance: "/api/test",
        timestamp: "2025-11-07T00:00:00Z",
      };

      const error = new ApiError(rfc7807Problem);

      // Simulate property loss
      Object.defineProperty(error, "title", { value: undefined, writable: true });
      Object.defineProperty(error, "status", { value: undefined, writable: true });

      // Create Proxy that throws when accessed
      const throwingProxy = new Proxy(
        {},
        {
          get() {
            throw new Error("Proxy access error");
          },
        },
      );

      Object.defineProperty(error, "cause", { value: throwingProxy, writable: true });

      const consoleErrorSpy = jest.spyOn(console, "error");

      render(<GlobalError error={error} reset={mockReset} />);

      // console.error should be called during reconstruction failure
      expect(consoleErrorSpy).toHaveBeenCalledWith(
        "Failed to reconstruct ApiError from cause:",
        expect.any(Error),
      );

      // Should still display error (falls back to original error)
      expect(screen.getByText("再試行")).toBeInTheDocument();

      consoleErrorSpy.mockRestore();
    });
  });
});
