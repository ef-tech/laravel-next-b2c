import { buildApiUrl, fetchUsers, ErrorHandlers, ApiError } from "./api";

describe("buildApiUrl", () => {
  it("builds versioned API URL correctly", () => {
    const loginUrl = buildApiUrl("admin/login");
    expect(loginUrl).toBe("http://localhost:13000/api/v1/admin/login");

    const dashboardUrl = buildApiUrl("admin/dashboard");
    expect(dashboardUrl).toBe("http://localhost:13000/api/v1/admin/dashboard");
  });

  it("handles endpoints with leading slash", () => {
    const url = buildApiUrl("/admin/login");
    expect(url).toBe("http://localhost:13000/api/v1/admin/login");
  });

  it("handles endpoints without leading slash", () => {
    const url = buildApiUrl("admin/logout");
    expect(url).toBe("http://localhost:13000/api/v1/admin/logout");
  });
});

describe("ErrorHandlers", () => {
  it("isAuthError returns true for auth errors", () => {
    const invalidCredentials = new ApiError("AUTH.INVALID_CREDENTIALS", "Invalid credentials", 401);
    const accountDisabled = new ApiError("AUTH.ACCOUNT_DISABLED", "Account disabled", 403);
    const tokenExpired = new ApiError("AUTH.TOKEN_EXPIRED", "Token expired", 401);

    expect(ErrorHandlers.isAuthError(invalidCredentials)).toBe(true);
    expect(ErrorHandlers.isAuthError(accountDisabled)).toBe(true);
    expect(ErrorHandlers.isAuthError(tokenExpired)).toBe(true);
  });

  it("isAuthError returns false for non-auth errors", () => {
    const validationError = new ApiError("VALIDATION_ERROR", "Validation failed", 422);
    const regularError = new Error("Regular error");

    expect(ErrorHandlers.isAuthError(validationError)).toBe(false);
    expect(ErrorHandlers.isAuthError(regularError)).toBe(false);
  });

  it("isValidationError returns true for validation errors", () => {
    const validationError = new ApiError("VALIDATION_ERROR", "Validation failed", 422);

    expect(ErrorHandlers.isValidationError(validationError)).toBe(true);
  });

  it("isInvalidCredentials returns true for invalid credentials errors", () => {
    const error = new ApiError("AUTH.INVALID_CREDENTIALS", "Invalid credentials", 401);

    expect(ErrorHandlers.isInvalidCredentials(error)).toBe(true);
  });

  it("isAccountDisabled returns true for account disabled errors", () => {
    const error = new ApiError("AUTH.ACCOUNT_DISABLED", "Account disabled", 403);

    expect(ErrorHandlers.isAccountDisabled(error)).toBe(true);
  });

  it("isTokenExpired returns true for token expired errors", () => {
    const error = new ApiError("AUTH.TOKEN_EXPIRED", "Token expired", 401);

    expect(ErrorHandlers.isTokenExpired(error)).toBe(true);
  });
});

describe("API Functions", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("fetches users successfully", async () => {
    global.fetch = jest.fn(() =>
      Promise.resolve({
        ok: true,
        json: () =>
          Promise.resolve([
            { id: 1, name: "John Doe", email: "john@example.com" },
            { id: 2, name: "Jane Smith", email: "jane@example.com" },
          ]),
      }),
    ) as jest.Mock;

    const users = await fetchUsers();

    expect(users).toHaveLength(2);
    expect(users[0].name).toBe("John Doe");
    expect(users[1].name).toBe("Jane Smith");
  });

  it("handles API errors with unified error response", async () => {
    global.fetch = jest.fn(() =>
      Promise.resolve({
        ok: false,
        status: 500,
        json: () =>
          Promise.resolve({
            code: "INTERNAL_ERROR",
            message: "Internal server error",
            trace_id: "req-test-123",
          }),
      }),
    ) as jest.Mock;

    await expect(fetchUsers()).rejects.toThrow(ApiError);

    try {
      await fetchUsers();
    } catch (error) {
      expect(error).toBeInstanceOf(ApiError);
      expect((error as ApiError).code).toBe("INTERNAL_ERROR");
      expect((error as ApiError).traceId).toBe("req-test-123");
    }
  });
});
