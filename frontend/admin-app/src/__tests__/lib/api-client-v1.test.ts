/**
 * API V1 Client Tests
 */

import {
  createToken,
  deleteAllTokens,
  deleteToken,
  getHealth,
  getTokens,
  getUser,
  login,
  logout,
  register,
  reportCspViolation,
} from "../../../../lib/api-client-v1";

// Mock fetch globally
global.fetch = jest.fn();

describe("API V1 Client", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe("HTTP Headers", () => {
    it("AcceptヘッダーがRFC 7807準拠で設定される", async () => {
      const mockResponse = { status: "ok" };
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      await getHealth();

      // RFC 7807準拠: application/problem+jsonを優先的にサポート
      // Content Negotiation: problem+jsonを先頭に配置し、後方互換性のためapplication/jsonも含める
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/health"),
        expect.objectContaining({
          headers: expect.objectContaining({
            Accept: "application/problem+json, application/json",
          }),
        }),
      );
    });
  });

  describe("getHealth", () => {
    it("should fetch health status", async () => {
      const mockResponse = { status: "ok" };
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await getHealth();

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/health"),
        expect.objectContaining({
          method: "GET",
          headers: expect.objectContaining({
            "Content-Type": "application/json",
          }),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("register", () => {
    it("should register a new user", async () => {
      const mockRequest = {
        name: "Test User",
        email: "test@example.com",
        password: "password123",
        password_confirmation: "password123",
      };
      const mockResponse = {
        token: "mock-token",
        user: { id: 1, name: "Test User", email: "test@example.com" },
      };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await register(mockRequest);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/users"),
        expect.objectContaining({
          method: "POST",
          body: JSON.stringify(mockRequest),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("login", () => {
    it("should login user", async () => {
      const mockRequest = {
        email: "test@example.com",
        password: "password123",
      };
      const mockResponse = {
        token: "mock-token",
        user: { id: 1, name: "Test User", email: "test@example.com" },
      };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await login(mockRequest);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/login"),
        expect.objectContaining({
          method: "POST",
          body: JSON.stringify(mockRequest),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("logout", () => {
    it("should logout user", async () => {
      const mockToken = "mock-token";
      const mockResponse = { message: "Logged out successfully" };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await logout(mockToken);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/logout"),
        expect.objectContaining({
          method: "POST",
          headers: expect.objectContaining({
            Authorization: `Bearer ${mockToken}`,
          }),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("getUser", () => {
    it("should fetch current user", async () => {
      const mockToken = "mock-token";
      const mockResponse = {
        id: 1,
        name: "Test User",
        email: "test@example.com",
      };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await getUser(mockToken);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/user"),
        expect.objectContaining({
          method: "GET",
          headers: expect.objectContaining({
            Authorization: `Bearer ${mockToken}`,
          }),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("createToken", () => {
    it("should create a new token", async () => {
      const mockToken = "mock-token";
      const mockRequest = { name: "My Token" };
      const mockResponse = {
        token: "new-token",
        name: "My Token",
        created_at: "2025-01-01T00:00:00.000000Z",
      };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await createToken(mockToken, mockRequest);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/tokens"),
        expect.objectContaining({
          method: "POST",
          body: JSON.stringify(mockRequest),
          headers: expect.objectContaining({
            Authorization: `Bearer ${mockToken}`,
          }),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("getTokens", () => {
    it("should fetch all tokens", async () => {
      const mockToken = "mock-token";
      const mockResponse = {
        tokens: [
          { id: 1, name: "Token 1", created_at: "2025-01-01T00:00:00.000000Z" },
          { id: 2, name: "Token 2", created_at: "2025-01-02T00:00:00.000000Z" },
        ],
      };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await getTokens(mockToken);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/tokens"),
        expect.objectContaining({
          method: "GET",
          headers: expect.objectContaining({
            Authorization: `Bearer ${mockToken}`,
          }),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("deleteToken", () => {
    it("should delete a specific token", async () => {
      const mockToken = "mock-token";
      const tokenId = 123;
      const mockResponse = { message: "Token deleted successfully" };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await deleteToken(mockToken, tokenId);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining(`/api/v1/tokens/${tokenId}`),
        expect.objectContaining({
          method: "DELETE",
          headers: expect.objectContaining({
            Authorization: `Bearer ${mockToken}`,
          }),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("deleteAllTokens", () => {
    it("should delete all tokens", async () => {
      const mockToken = "mock-token";
      const mockResponse = { message: "All tokens deleted successfully" };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      });

      const result = await deleteAllTokens(mockToken);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/tokens"),
        expect.objectContaining({
          method: "DELETE",
          headers: expect.objectContaining({
            Authorization: `Bearer ${mockToken}`,
          }),
        }),
      );
      expect(result).toEqual(mockResponse);
    });
  });

  describe("reportCspViolation", () => {
    it("should report CSP violation", async () => {
      const mockRequest = {
        "csp-report": {
          "document-uri": "https://example.com/",
          "violated-directive": "script-src",
        },
      };

      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        status: 204,
        json: async () => ({}),
      });

      await reportCspViolation(mockRequest);

      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining("/api/v1/csp/report"),
        expect.objectContaining({
          method: "POST",
          body: JSON.stringify(mockRequest),
        }),
      );
    });
  });

  describe("Error handling", () => {
    it("should throw error on failed request", async () => {
      const mockError = { message: "Authentication failed" };
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: false,
        json: async () => mockError,
      });

      await expect(login({ email: "test@example.com", password: "wrong" })).rejects.toThrow(
        "Authentication failed",
      );
    });
  });
});
