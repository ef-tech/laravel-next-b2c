import { ApiError, handleApiError, ApiErrorResponse } from "./api-error-handler";

describe("ApiError", () => {
  it("creates ApiError with error code and message", () => {
    const error = new ApiError(
      "AUTH.INVALID_CREDENTIALS",
      "メールアドレスまたはパスワードが正しくありません",
      401,
      "req-123",
    );

    expect(error.code).toBe("AUTH.INVALID_CREDENTIALS");
    expect(error.message).toBe("メールアドレスまたはパスワードが正しくありません");
    expect(error.statusCode).toBe(401);
    expect(error.traceId).toBe("req-123");
  });

  it("creates ApiError with validation errors", () => {
    const validationErrors = {
      email: ["メールアドレスの形式が正しくありません"],
      password: ["パスワードは8文字以上で入力してください"],
    };

    const error = new ApiError(
      "VALIDATION_ERROR",
      "入力内容に誤りがあります",
      422,
      "req-456",
      validationErrors,
    );

    expect(error.code).toBe("VALIDATION_ERROR");
    expect(error.errors).toEqual(validationErrors);
  });
});

describe("handleApiError", () => {
  const mockConsoleError = jest.spyOn(console, "error").mockImplementation();

  beforeEach(() => {
    mockConsoleError.mockClear();
  });

  afterAll(() => {
    mockConsoleError.mockRestore();
  });

  it("handles 401 InvalidCredentials error", async () => {
    const mockResponse = {
      ok: false,
      status: 401,
      json: async () =>
        ({
          code: "AUTH.INVALID_CREDENTIALS",
          message: "メールアドレスまたはパスワードが正しくありません",
          trace_id: "req-789",
        }) as ApiErrorResponse,
    } as Response;

    const error = await handleApiError(mockResponse).catch((e) => e);

    expect(error).toBeInstanceOf(ApiError);
    expect(error.code).toBe("AUTH.INVALID_CREDENTIALS");
    expect(error.statusCode).toBe(401);
    expect(error.traceId).toBe("req-789");
  });

  it("handles 403 AccountDisabled error", async () => {
    const mockResponse = {
      ok: false,
      status: 403,
      json: async () =>
        ({
          code: "AUTH.ACCOUNT_DISABLED",
          message: "アカウントが無効化されています",
          trace_id: "req-abc",
        }) as ApiErrorResponse,
    } as Response;

    const error = await handleApiError(mockResponse).catch((e) => e);

    expect(error).toBeInstanceOf(ApiError);
    expect(error.code).toBe("AUTH.ACCOUNT_DISABLED");
    expect(error.statusCode).toBe(403);
  });

  it("handles 422 Validation error with field errors", async () => {
    const mockResponse = {
      ok: false,
      status: 422,
      json: async () =>
        ({
          code: "VALIDATION_ERROR",
          message: "入力内容に誤りがあります",
          errors: {
            email: ["メールアドレスの形式が正しくありません"],
            password: ["パスワードは8文字以上で入力してください"],
          },
          trace_id: "req-def",
        }) as ApiErrorResponse,
    } as Response;

    const error = await handleApiError(mockResponse).catch((e) => e);

    expect(error).toBeInstanceOf(ApiError);
    expect(error.code).toBe("VALIDATION_ERROR");
    expect(error.errors).toHaveProperty("email");
    expect(error.errors).toHaveProperty("password");
  });

  it("logs trace_id for debugging", async () => {
    const mockResponse = {
      ok: false,
      status: 500,
      json: async () =>
        ({
          code: "INTERNAL_ERROR",
          message: "サーバーエラーが発生しました",
          trace_id: "req-error-123",
        }) as ApiErrorResponse,
    } as Response;

    await handleApiError(mockResponse).catch(() => {
      // Catch the error to prevent test failure
    });

    // trace_idがログに記録されることを確認
    expect(mockConsoleError).toHaveBeenCalledWith(
      expect.stringContaining("trace_id: req-error-123"),
    );
  });

  it("handles non-JSON error response", async () => {
    const mockResponse = {
      ok: false,
      status: 500,
      statusText: "Internal Server Error",
      json: async () => {
        throw new Error("JSON parse error");
      },
    } as Response;

    const error = await handleApiError(mockResponse).catch((e) => e);

    expect(error).toBeInstanceOf(ApiError);
    expect(error.code).toBe("UNKNOWN_ERROR");
    expect(error.message).toContain("Internal Server Error");
  });
});
