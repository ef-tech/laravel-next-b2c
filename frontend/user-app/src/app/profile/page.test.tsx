import { render, screen, waitFor } from "@testing-library/react";
import ProfilePage from "./page";
import { useAuth } from "@/contexts/AuthContext";
import { useRouter } from "next/navigation";

// next/navigationのモック
jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
}));

// AuthContextのモック
jest.mock("@/contexts/AuthContext", () => ({
  useAuth: jest.fn(),
}));

describe("ProfilePage", () => {
  const mockPush = jest.fn();
  const mockFetchUserProfile = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (useRouter as jest.Mock).mockReturnValue({
      push: mockPush,
    });
  });

  describe("認証済みユーザー", () => {
    it("ユーザー情報が表示される", () => {
      (useAuth as jest.Mock).mockReturnValue({
        user: {
          id: 1,
          name: "Test User",
          email: "user@example.com",
        },
        isAuthenticated: true,
        isLoading: false,
        fetchUserProfile: mockFetchUserProfile,
      });

      render(<ProfilePage />);

      expect(screen.getByText("プロフィール")).toBeInTheDocument();
      expect(screen.getByText("Test User")).toBeInTheDocument();
      expect(screen.getByText("user@example.com")).toBeInTheDocument();
    });

    it("初回表示時にfetchUserProfile()が呼ばれる", () => {
      (useAuth as jest.Mock).mockReturnValue({
        user: {
          id: 1,
          name: "Test User",
          email: "user@example.com",
        },
        isAuthenticated: true,
        isLoading: false,
        fetchUserProfile: mockFetchUserProfile,
      });

      render(<ProfilePage />);

      expect(mockFetchUserProfile).toHaveBeenCalledTimes(1);
    });

    it("ローディング中は読み込み表示される", () => {
      (useAuth as jest.Mock).mockReturnValue({
        user: null,
        isAuthenticated: false,
        isLoading: true,
        fetchUserProfile: mockFetchUserProfile,
      });

      render(<ProfilePage />);

      expect(screen.getByText(/読み込み中/i)).toBeInTheDocument();
    });
  });

  describe("未認証ユーザー", () => {
    it("未認証の場合、ログイン画面へリダイレクトする", async () => {
      (useAuth as jest.Mock).mockReturnValue({
        user: null,
        isAuthenticated: false,
        isLoading: false,
        fetchUserProfile: mockFetchUserProfile,
      });

      render(<ProfilePage />);

      await waitFor(() => {
        expect(mockPush).toHaveBeenCalledWith("/login");
      });
    });
  });

  describe("エラーハンドリング", () => {
    it("プロフィール取得失敗時にエラーメッセージを表示する", async () => {
      mockFetchUserProfile.mockRejectedValueOnce(new Error("Failed to fetch profile"));

      (useAuth as jest.Mock).mockReturnValue({
        user: null,
        isAuthenticated: true,
        isLoading: false,
        fetchUserProfile: mockFetchUserProfile,
      });

      render(<ProfilePage />);

      await waitFor(() => {
        expect(screen.getByText(/プロフィール情報の取得に失敗しました/i)).toBeInTheDocument();
      });
    });
  });
});
