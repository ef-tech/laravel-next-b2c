/**
 * Error Boundary Component Tests (User App)
 *
 * Task 10: Error Boundary Component Testsの実装
 * - Task 10.1: User App Error Boundary日本語ロケールテスト実装
 * - Task 10.2: User App Error Boundary英語ロケールテスト実装
 *
 * Requirements:
 * - REQ-8.3: Error Boundary i18n tests with NextIntlClientProvider
 * - REQ-8.4: 90%+ code coverage for Error Boundary components
 */

import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import ErrorBoundary from "../error";
import { NetworkError } from "@/lib/network-error";
import { ApiError } from "@/lib/api-error";

// RFC 7807 Problem Details interface for error.cause
interface RFC7807Cause {
  status: number;
  title: string;
  detail: string;
  type: string;
  instance: string;
  trace_id: string;
}

// Mock Next.js Link component
jest.mock("next/link", () => {
  const MockLink = ({ children, href }: { children: React.ReactNode; href: string }) => {
    return <a href={href}>{children}</a>;
  };
  MockLink.displayName = "MockLink";
  return MockLink;
});

// 日本語翻訳メッセージ
const messagesJa = {
  errors: {
    network: {
      timeout: "リクエストがタイムアウトしました。しばらくしてから再度お試しください。",
      connection:
        "ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。",
      unknown: "予期しないエラーが発生しました。しばらくしてから再度お試しください。",
    },
    boundary: {
      title: "エラーが発生しました",
      retry: "再試行",
      home: "ホームに戻る",
      status: "ステータスコード",
      requestId: "Request ID",
      networkError: "ネットワークエラー",
      timeout: "タイムアウト",
      connectionError: "接続エラー",
      retryableMessage: "このエラーは再試行可能です。しばらくしてから再度お試しください。",
    },
    validation: {
      title: "入力エラー",
    },
    global: {
      title: "予期しないエラーが発生しました",
      retry: "再試行",
      errorId: "Error ID",
      contactMessage: "お問い合わせの際は、このIDをお伝えください",
    },
  },
};

// 英語翻訳メッセージ
const messagesEn = {
  errors: {
    network: {
      timeout: "The request timed out. Please try again later.",
      connection:
        "A network connection problem occurred. Please check your internet connection and try again.",
      unknown: "An unexpected error occurred. Please try again later.",
    },
    boundary: {
      title: "An error occurred",
      retry: "Retry",
      home: "Go to Home",
      status: "Status Code",
      requestId: "Request ID",
      networkError: "Network Error",
      timeout: "Timeout",
      connectionError: "Connection Error",
      retryableMessage: "This error is retryable. Please try again later.",
    },
    validation: {
      title: "Validation Errors",
    },
    global: {
      title: "An unexpected error occurred",
      retry: "Retry",
      errorId: "Error ID",
      contactMessage: "Please provide this ID when contacting support",
    },
  },
};

describe("Error Boundary (User App)", () => {
  const mockReset = jest.fn();

  beforeEach(() => {
    mockReset.mockClear();
    // Suppress console.error for cleaner test output
    jest.spyOn(console, "error").mockImplementation(() => {});
    // Mock window.location to prevent actual redirects in tests
    delete (window as Partial<Window>).location;
    window.location = {
      href: "http://localhost/",
      pathname: "/",
      search: "",
      assign: jest.fn(),
      replace: jest.fn(),
      reload: jest.fn(),
    } as Location;
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  /**
   * Task 10.1: User App Error Boundary日本語ロケールテスト実装
   * - NextIntlClientProviderでラップ
   * - ja.jsonメッセージを使用
   * - NetworkErrorで日本語メッセージが表示されることを確認
   * - ApiErrorでステータスコードと検証エラーが日本語表示されることを確認
   * - digestがある場合にRequest IDが日本語表示されることを確認
   */
  describe("Japanese locale (ja)", () => {
    const renderWithJa = (ui: React.ReactElement) =>
      render(
        <NextIntlClientProvider locale="ja" messages={messagesJa}>
          {ui}
        </NextIntlClientProvider>,
      );

    describe("NetworkError", () => {
      it("タイムアウトエラーで日本語メッセージを表示する", () => {
        const abortError = new Error("Aborted");
        abortError.name = "AbortError";
        const error = NetworkError.fromFetchError(abortError);

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // タイトル確認
        expect(screen.getByText("ネットワークエラー")).toBeInTheDocument();
        expect(screen.getByText("タイムアウト")).toBeInTheDocument();

        // 日本語メッセージ確認
        expect(
          screen.getByText(
            "リクエストがタイムアウトしました。しばらくしてから再度お試しください。",
          ),
        ).toBeInTheDocument();

        // 再試行可能メッセージ確認
        expect(
          screen.getByText("このエラーは再試行可能です。しばらくしてから再度お試しください。"),
        ).toBeInTheDocument();

        // ボタン確認
        expect(screen.getByRole("button", { name: "再試行" })).toBeInTheDocument();
        expect(screen.getByRole("link", { name: "ホームに戻る" })).toBeInTheDocument();
      });

      it("接続エラーで日本語メッセージを表示する", () => {
        const fetchError = new TypeError("Failed to fetch");
        const error = NetworkError.fromFetchError(fetchError);

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // タイトル確認
        expect(screen.getByText("ネットワークエラー")).toBeInTheDocument();
        expect(screen.getByText("接続エラー")).toBeInTheDocument();

        // 日本語メッセージ確認
        expect(
          screen.getByText(
            "ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。",
          ),
        ).toBeInTheDocument();

        // 再試行可能メッセージ確認
        expect(
          screen.getByText("このエラーは再試行可能です。しばらくしてから再度お試しください。"),
        ).toBeInTheDocument();
      });

      it("不明なネットワークエラーで日本語メッセージを表示する", () => {
        const unknownError = new Error("Unknown network issue");
        const error = NetworkError.fromFetchError(unknownError);

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // タイトル確認（複数要素がある場合は getAllByText を使用）
        expect(screen.getAllByText("ネットワークエラー").length).toBeGreaterThan(0);

        // 日本語メッセージ確認
        expect(
          screen.getByText("予期しないエラーが発生しました。しばらくしてから再度お試しください。"),
        ).toBeInTheDocument();
      });
    });

    describe("ApiError", () => {
      it("ステータスコードと日本語メッセージを表示する", () => {
        const error = new ApiError({
          status: 500,
          title: "Internal Server Error",
          detail: "サーバーエラーが発生しました",
          type: "about:blank",
          instance: "/api/v1/users",
          trace_id: "req-12345",
        });

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // タイトル確認
        expect(screen.getByText("エラーが発生しました")).toBeInTheDocument();
        expect(screen.getByText("ステータスコード: 500")).toBeInTheDocument();

        // RFC 7807 タイトル確認
        expect(screen.getByText("Internal Server Error")).toBeInTheDocument();

        // Request ID確認（日本語ラベル）
        expect(screen.getByText("Request ID:")).toBeInTheDocument();
        expect(screen.getByText("req-12345")).toBeInTheDocument();

        // ボタン確認
        expect(screen.getByRole("button", { name: "再試行" })).toBeInTheDocument();
        expect(screen.getByRole("link", { name: "ホームに戻る" })).toBeInTheDocument();
      });

      it("バリデーションエラーを日本語で表示する", () => {
        const error = new ApiError({
          status: 400,
          title: "Validation Error",
          detail: "入力値が不正です",
          type: "about:blank",
          instance: "/api/v1/users",
          trace_id: "req-12345",
          errors: {
            email: ["メールアドレスは必須です", "メールアドレスの形式が不正です"],
            password: ["パスワードは8文字以上である必要があります"],
          },
        });

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // バリデーションエラータイトル確認（日本語）
        expect(screen.getByText("入力エラー:")).toBeInTheDocument();

        // バリデーションエラーの詳細確認
        expect(screen.getByText(/email:/)).toBeInTheDocument();
        expect(
          screen.getByText(/メールアドレスは必須です, メールアドレスの形式が不正です/),
        ).toBeInTheDocument();
        expect(screen.getByText(/password:/)).toBeInTheDocument();
        expect(screen.getByText(/パスワードは8文字以上である必要があります/)).toBeInTheDocument();
      });

      it("digestがある場合にRequest IDを日本語で表示する", () => {
        const error = new ApiError({
          status: 500,
          title: "Internal Server Error",
          detail: "サーバーエラーが発生しました",
          type: "about:blank",
          instance: "/api/v1/users",
          trace_id: "req-trace-67890",
        });
        error.digest = "digest-xyz-123";

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // Request ID確認（trace_idが優先される）
        expect(screen.getByText("Request ID:")).toBeInTheDocument();
        expect(screen.getByText("req-trace-67890")).toBeInTheDocument();
      });
    });

    describe("Generic Error", () => {
      it("汎用エラーで日本語メッセージを表示する（開発環境）", () => {
        const originalEnv = process.env.NODE_ENV;
        process.env.NODE_ENV = "development";

        const error = new Error("Generic error occurred");
        error.digest = "error-digest-abc";

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // タイトル確認
        expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();

        // エラーメッセージ確認（開発環境では生のメッセージ表示）
        expect(screen.getByText("Generic error occurred")).toBeInTheDocument();

        // Error ID確認（日本語ラベル）
        expect(screen.getByText("Error ID:")).toBeInTheDocument();
        expect(screen.getByText("error-digest-abc")).toBeInTheDocument();

        // お問い合わせメッセージ確認
        expect(screen.getByText("お問い合わせの際は、このIDをお伝えください")).toBeInTheDocument();

        // ボタン確認
        expect(screen.getByRole("button", { name: "再試行" })).toBeInTheDocument();

        process.env.NODE_ENV = originalEnv;
      });

      it("汎用エラーで日本語メッセージを表示する（本番環境）", () => {
        const originalEnv = process.env.NODE_ENV;
        process.env.NODE_ENV = "production";

        const error = new Error("Internal error details should be hidden");
        error.digest = "error-digest-def";

        renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

        // タイトル確認
        expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();

        // エラーメッセージ確認（本番環境では汎用メッセージ）
        expect(
          screen.getByText("予期しないエラーが発生しました。しばらくしてから再度お試しください。"),
        ).toBeInTheDocument();

        // Error ID確認
        expect(screen.getByText("Error ID:")).toBeInTheDocument();

        process.env.NODE_ENV = originalEnv;
      });
    });
  });

  /**
   * Task 10.2: User App Error Boundary英語ロケールテスト実装
   * - NextIntlClientProviderでラップ（en locale）
   * - en.jsonメッセージを使用
   * - NetworkErrorで英語メッセージが表示されることを確認
   * - ApiErrorで英語メッセージが表示されることを確認
   * - 全UI要素（タイトル、ボタン、ラベル）の英語表示を検証
   */
  describe("English locale (en)", () => {
    const renderWithEn = (ui: React.ReactElement) =>
      render(
        <NextIntlClientProvider locale="en" messages={messagesEn}>
          {ui}
        </NextIntlClientProvider>,
      );

    describe("NetworkError", () => {
      it("タイムアウトエラーで英語メッセージを表示する", () => {
        const abortError = new Error("Aborted");
        abortError.name = "AbortError";
        const error = NetworkError.fromFetchError(abortError);

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Title check
        expect(screen.getByText("Network Error")).toBeInTheDocument();
        expect(screen.getByText("Timeout")).toBeInTheDocument();

        // English message check
        expect(
          screen.getByText("The request timed out. Please try again later."),
        ).toBeInTheDocument();

        // Retryable message check
        expect(
          screen.getByText("This error is retryable. Please try again later."),
        ).toBeInTheDocument();

        // Button check
        expect(screen.getByRole("button", { name: "Retry" })).toBeInTheDocument();
        expect(screen.getByRole("link", { name: "Go to Home" })).toBeInTheDocument();
      });

      it("接続エラーで英語メッセージを表示する", () => {
        const fetchError = new TypeError("Failed to fetch");
        const error = NetworkError.fromFetchError(fetchError);

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Title check
        expect(screen.getByText("Network Error")).toBeInTheDocument();
        expect(screen.getByText("Connection Error")).toBeInTheDocument();

        // English message check
        expect(
          screen.getByText(
            "A network connection problem occurred. Please check your internet connection and try again.",
          ),
        ).toBeInTheDocument();
      });

      it("不明なネットワークエラーで英語メッセージを表示する", () => {
        const unknownError = new Error("Unknown network issue");
        const error = NetworkError.fromFetchError(unknownError);

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Title check (複数要素がある場合は getAllByText を使用)
        expect(screen.getAllByText("Network Error").length).toBeGreaterThan(0);

        // English message check
        expect(
          screen.getByText("An unexpected error occurred. Please try again later."),
        ).toBeInTheDocument();
      });
    });

    describe("ApiError", () => {
      it("ステータスコードと英語メッセージを表示する", () => {
        const error = new ApiError({
          status: 500,
          title: "Internal Server Error",
          detail: "A server error occurred",
          type: "about:blank",
          instance: "/api/v1/users",
          trace_id: "req-12345",
        });

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Title check
        expect(screen.getByText("An error occurred")).toBeInTheDocument();
        expect(screen.getByText("Status Code: 500")).toBeInTheDocument();

        // RFC 7807 title check
        expect(screen.getByText("Internal Server Error")).toBeInTheDocument();

        // Request ID check (English label)
        expect(screen.getByText("Request ID:")).toBeInTheDocument();
        expect(screen.getByText("req-12345")).toBeInTheDocument();

        // Button check
        expect(screen.getByRole("button", { name: "Retry" })).toBeInTheDocument();
        expect(screen.getByRole("link", { name: "Go to Home" })).toBeInTheDocument();
      });

      it("バリデーションエラーを英語で表示する", () => {
        const error = new ApiError({
          status: 400,
          title: "Validation Error",
          detail: "Invalid input values",
          type: "about:blank",
          instance: "/api/v1/users",
          trace_id: "req-12345",
          errors: {
            email: ["Email is required", "Invalid email format"],
            password: ["Password must be at least 8 characters"],
          },
        });

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Validation error title check (English)
        expect(screen.getByText("Validation Errors:")).toBeInTheDocument();

        // Validation error details check
        expect(screen.getByText(/email:/)).toBeInTheDocument();
        expect(screen.getByText(/Email is required, Invalid email format/)).toBeInTheDocument();
        expect(screen.getByText(/password:/)).toBeInTheDocument();
        expect(screen.getByText(/Password must be at least 8 characters/)).toBeInTheDocument();
      });

      it("digestがある場合にRequest IDを英語で表示する", () => {
        const error = new ApiError({
          status: 500,
          title: "Internal Server Error",
          detail: "A server error occurred",
          type: "about:blank",
          instance: "/api/v1/users",
          trace_id: "req-trace-67890",
        });
        error.digest = "digest-xyz-123";

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Request ID check (trace_id takes priority)
        expect(screen.getByText("Request ID:")).toBeInTheDocument();
        expect(screen.getByText("req-trace-67890")).toBeInTheDocument();
      });
    });

    describe("Generic Error", () => {
      it("汎用エラーで英語メッセージを表示する（開発環境）", () => {
        const originalEnv = process.env.NODE_ENV;
        process.env.NODE_ENV = "development";

        const error = new Error("Generic error occurred");
        error.digest = "error-digest-abc";

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Title check
        expect(screen.getByText("An unexpected error occurred")).toBeInTheDocument();

        // Error message check (development environment shows raw message)
        expect(screen.getByText("Generic error occurred")).toBeInTheDocument();

        // Error ID check (English label)
        expect(screen.getByText("Error ID:")).toBeInTheDocument();
        expect(screen.getByText("error-digest-abc")).toBeInTheDocument();

        // Contact message check
        expect(
          screen.getByText("Please provide this ID when contacting support"),
        ).toBeInTheDocument();

        // Button check
        expect(screen.getByRole("button", { name: "Retry" })).toBeInTheDocument();

        process.env.NODE_ENV = originalEnv;
      });

      it("汎用エラーで英語メッセージを表示する（本番環境）", () => {
        const originalEnv = process.env.NODE_ENV;
        process.env.NODE_ENV = "production";

        const error = new Error("Internal error details should be hidden");
        error.digest = "error-digest-def";

        renderWithEn(<ErrorBoundary error={error} reset={mockReset} />);

        // Title check
        expect(screen.getByText("An unexpected error occurred")).toBeInTheDocument();

        // Error message check (production environment shows generic message)
        expect(
          screen.getByText("An unexpected error occurred. Please try again later."),
        ).toBeInTheDocument();

        // Error ID check
        expect(screen.getByText("Error ID:")).toBeInTheDocument();

        process.env.NODE_ENV = originalEnv;
      });
    });
  });

  /**
   * Task 10.4: Error Boundary Component Tests 90%以上カバレッジ達成
   * - 未カバーの行（L60-63, L79-89, L93-97, L102-105）をテスト
   */
  describe("Edge Cases (カバレッジ向上)", () => {
    const renderWithJa = (ui: React.ReactElement) =>
      render(
        <NextIntlClientProvider locale="ja" messages={messagesJa}>
          {ui}
        </NextIntlClientProvider>,
      );

    it("ApiErrorプロパティ喪失ケース（error.causeから再構築）", () => {
      // ApiErrorインスタンスだがプロパティが失われているケース（Next.jsシリアライズ問題）
      const apiError = new ApiError({
        status: 500,
        title: "Internal Server Error",
        detail: "サーバーエラー",
        type: "about:blank",
        instance: "/api/v1/users",
        trace_id: "req-reconstruct",
      });

      // プロパティを上書きしてcauseだけ残す（Next.jsシリアライズをシミュレート）
      // readonly プロパティをundefinedに変更
      Object.defineProperty(apiError, "title", {
        value: undefined,
        writable: false,
        configurable: true,
      });
      Object.defineProperty(apiError, "status", {
        value: undefined,
        writable: false,
        configurable: true,
      });

      renderWithJa(<ErrorBoundary error={apiError} reset={mockReset} />);

      // causeから再構築されたApiErrorが表示されることを確認
      expect(screen.getByText("エラーが発生しました")).toBeInTheDocument();
      expect(screen.getByText("ステータスコード: 500")).toBeInTheDocument();
      expect(screen.getByText("req-reconstruct")).toBeInTheDocument();
    });

    it("ApiError名前チェック経由の再構築（instanceof失敗ケース）", () => {
      // instanceofが失敗するがnameがApiErrorのケース
      const error = new Error("ApiError-like error") as Error & { cause: RFC7807Cause };
      error.name = "ApiError";
      error.cause = {
        status: 503,
        title: "Service Unavailable",
        detail: "サービスが利用できません",
        type: "about:blank",
        instance: "/api/v1/health",
        trace_id: "req-503",
      };

      renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

      // causeから再構築されたApiErrorが表示されることを確認
      expect(screen.getByText("ステータスコード: 503")).toBeInTheDocument();
      expect(screen.getByText("Service Unavailable")).toBeInTheDocument();
      expect(screen.getByText("req-503")).toBeInTheDocument();
    });

    it("Generic error causeからApiError再構築", () => {
      // Generic errorだがcauseにRFC 7807準拠データがあるケース
      const error = new Error("Generic error") as Error & { cause: RFC7807Cause };
      error.cause = {
        status: 504,
        title: "Gateway Timeout",
        detail: "ゲートウェイタイムアウト",
        type: "about:blank",
        instance: "/api/v1/external",
        trace_id: "req-504",
      };

      renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

      // causeから再構築されたApiErrorが表示されることを確認
      expect(screen.getByText("ステータスコード: 504")).toBeInTheDocument();
      expect(screen.getByText("Gateway Timeout")).toBeInTheDocument();
      expect(screen.getByText("req-504")).toBeInTheDocument();
    });

    it("ApiError再構築失敗時のフォールバック（causeが不正）", () => {
      // console.errorをスパイ
      const consoleErrorSpy = jest.spyOn(console, "error");

      // ApiErrorインスタンスだがプロパティ喪失 & causeがゲッターで例外を投げる
      const apiError = new ApiError({
        status: 500,
        title: "Internal Server Error",
        detail: "サーバーエラー",
        type: "about:blank",
        instance: "/api/v1/users",
        trace_id: "req-fallback",
      });

      // プロパティを上書きしてundefinedに
      Object.defineProperty(apiError, "title", {
        value: undefined,
        writable: false,
        configurable: true,
      });
      Object.defineProperty(apiError, "status", {
        value: undefined,
        writable: false,
        configurable: true,
      });

      // causeに、detailプロパティへのアクセス時に例外を投げるオブジェクトを設定
      const poisonedCause = {
        get detail() {
          throw new Error("Poisoned detail getter");
        },
        status: 500,
        title: "Title",
        type: "about:blank",
        instance: "/test",
        trace_id: "test-id",
      };
      Object.defineProperty(apiError, "cause", {
        value: poisonedCause,
        writable: false,
        configurable: true,
      });

      // 再構築失敗時はcatch節で元のerrorを使う（L84-85）
      renderWithJa(<ErrorBoundary error={apiError} reset={mockReset} />);

      // フォールバック: L85で apiError = error とするので、ApiErrorインスタンスとして表示される
      // ただしstatusとtitleがundefinedなので "ステータスコード: undefined" と表示される
      expect(screen.getByText("エラーが発生しました")).toBeInTheDocument();
      expect(screen.getByText(/ステータスコード:/)).toBeInTheDocument();

      // catch節でconsole.errorが呼ばれたことを確認（L84）
      expect(consoleErrorSpy).toHaveBeenCalledWith(
        "Failed to reconstruct ApiError from cause:",
        expect.any(Error),
      );
    });

    it("ApiError名前チェック経由の再構築失敗（L97）", () => {
      // console.errorをスパイ
      const consoleErrorSpy = jest.spyOn(console, "error");

      // error.name = "ApiError"だがinstanceofは失敗、causeのdetailゲッターが例外を投げる
      const error = new Error("ApiError-like error") as Error & { cause: unknown };
      error.name = "ApiError"; // L91の条件を満たすためにerror.nameを設定

      const poisonedCause = {
        get detail() {
          throw new Error("Poisoned detail in name check");
        },
        status: 503,
        title: "Service Unavailable",
        type: "about:blank",
        instance: "/test",
        trace_id: "test-97",
      };
      error.cause = poisonedCause;

      renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

      // L97のcatch節で再構築失敗、apiErrorはnullのままなのでGeneric errorとして表示
      expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();

      // catch節でconsole.errorが呼ばれたことを確認（L97）
      expect(consoleErrorSpy).toHaveBeenCalledWith(
        "Failed to reconstruct ApiError from name check:",
        expect.any(Error),
      );
    });

    it("Generic error causeから再構築失敗（L105）", () => {
      // console.errorをスパイ
      const consoleErrorSpy = jest.spyOn(console, "error");

      // Generic errorでcauseのdetailゲッターが例外を投げる
      const error = new Error("Generic error") as Error & { cause: unknown };
      error.name = "TypeError"; // ApiErrorではないことを明示

      const poisonedCause = {
        get detail() {
          throw new Error("Poisoned detail in generic cause");
        },
        status: 504,
        title: "Gateway Timeout",
        type: "about:blank",
        instance: "/test",
        trace_id: "test-105",
      };
      error.cause = poisonedCause;

      renderWithJa(<ErrorBoundary error={error} reset={mockReset} />);

      // L105のcatch節で再構築失敗、apiErrorはnullのままなのでGeneric errorとして表示
      expect(screen.getByText("予期しないエラーが発生しました")).toBeInTheDocument();

      // catch節でconsole.errorが呼ばれたことを確認（L105）
      expect(consoleErrorSpy).toHaveBeenCalledWith(
        "Failed to reconstruct ApiError from generic cause:",
        expect.any(Error),
      );
    });

    it("ApiErrorインスタンスだがプロパティ喪失でcauseもない（L89）", () => {
      // ApiErrorインスタンスだが、titleとstatusがundefinedで、causeもnull
      const apiError = new ApiError({
        status: 500,
        title: "Internal Server Error",
        detail: "サーバーエラー",
        type: "about:blank",
        instance: "/api/v1/users",
        trace_id: "req-no-cause",
      });

      // プロパティを上書きしてundefinedに、causeをnullに設定
      Object.defineProperty(apiError, "title", {
        value: undefined,
        writable: false,
        configurable: true,
      });
      Object.defineProperty(apiError, "status", {
        value: undefined,
        writable: false,
        configurable: true,
      });
      Object.defineProperty(apiError, "cause", {
        value: null, // causeがnull
        writable: false,
        configurable: true,
      });

      // L87-89のelse節に入る: ApiErrorインスタンスだがcauseなし
      renderWithJa(<ErrorBoundary error={apiError} reset={mockReset} />);

      // L89でapiError = errorとなり、ApiErrorとして表示される（プロパティがundefinedでも）
      expect(screen.getByText("エラーが発生しました")).toBeInTheDocument();
      expect(screen.getByText(/ステータスコード:/)).toBeInTheDocument();
    });
  });
});
