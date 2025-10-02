import { renderHook, waitFor } from "@testing-library/react";
import { useSearchParams } from "next/navigation";
import { useAuth } from "./useAuth";

jest.mock("next/navigation", () => ({
  useSearchParams: jest.fn(),
}));

describe("useAuth Hook", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("returns authentication token from query params", () => {
    const mockSearchParams = {
      get: jest.fn((key: string) => (key === "token" ? "abc123" : null)),
    };
    (useSearchParams as jest.Mock).mockReturnValue(mockSearchParams);

    const { result } = renderHook(() => useAuth());

    expect(result.current.token).toBe("abc123");
  });

  it("fetches user data on mount", async () => {
    const mockSearchParams = {
      get: jest.fn((key: string) => (key === "token" ? "abc123" : null)),
    };
    (useSearchParams as jest.Mock).mockReturnValue(mockSearchParams);

    global.fetch = jest.fn(() =>
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ id: 1, name: "John Doe", email: "john@example.com" }),
      }),
    ) as jest.Mock;

    const { result } = renderHook(() => useAuth());

    expect(result.current.loading).toBe(true);

    await waitFor(() => {
      expect(result.current.loading).toBe(false);
    });

    expect(result.current.user).toEqual({
      id: 1,
      name: "John Doe",
      email: "john@example.com",
    });
  });
});
