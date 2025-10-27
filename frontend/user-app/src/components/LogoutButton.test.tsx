import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useRouter } from "next/navigation";
import LogoutButton from "./LogoutButton";
import { useAuth } from "@/contexts/AuthContext";

// next/navigationのモック
jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
}));

// AuthContextのモック
jest.mock("@/contexts/AuthContext", () => ({
  useAuth: jest.fn(),
}));

describe("LogoutButton", () => {
  const mockPush = jest.fn();
  const mockLogout = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (useRouter as jest.Mock).mockReturnValue({
      push: mockPush,
    });
    (useAuth as jest.Mock).mockReturnValue({
      logout: mockLogout,
      isLoading: false,
    });
  });

  it("ログアウトボタンが正しく表示される", () => {
    render(<LogoutButton />);

    expect(screen.getByRole("button", { name: /ログアウト/i })).toBeInTheDocument();
  });

  it("ボタンクリック時にlogout()が呼ばれる", async () => {
    const user = userEvent.setup();
    mockLogout.mockResolvedValueOnce(undefined);

    render(<LogoutButton />);

    const logoutButton = screen.getByRole("button", { name: /ログアウト/i });
    await user.click(logoutButton);

    await waitFor(() => {
      expect(mockLogout).toHaveBeenCalledTimes(1);
    });
  });

  it("ログアウト成功時にログイン画面へリダイレクトする", async () => {
    const user = userEvent.setup();
    mockLogout.mockResolvedValueOnce(undefined);

    render(<LogoutButton />);

    const logoutButton = screen.getByRole("button", { name: /ログアウト/i });
    await user.click(logoutButton);

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("ログアウト処理中はボタンが無効化される", () => {
    (useAuth as jest.Mock).mockReturnValue({
      logout: mockLogout,
      isLoading: true,
    });

    render(<LogoutButton />);

    const logoutButton = screen.getByRole("button", { name: /処理中/i });
    expect(logoutButton).toBeDisabled();
  });

  it("ログアウトエラー時でもリダイレクトする", async () => {
    const user = userEvent.setup();
    mockLogout.mockRejectedValueOnce(new Error("Logout failed"));

    render(<LogoutButton />);

    const logoutButton = screen.getByRole("button", { name: /ログアウト/i });
    await user.click(logoutButton);

    // エラーが発生してもリダイレクトは実行される
    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/login");
    });
  });

  it("カスタムクラス名を適用できる", () => {
    render(<LogoutButton className="custom-class" />);

    const logoutButton = screen.getByRole("button", { name: /ログアウト/i });
    expect(logoutButton).toHaveClass("custom-class");
  });

  it("children propを使用してボタンテキストをカスタマイズできる", () => {
    render(<LogoutButton>カスタムログアウト</LogoutButton>);

    expect(screen.getByRole("button", { name: /カスタムログアウト/i })).toBeInTheDocument();
  });
});
