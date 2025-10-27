import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { useRouter } from "next/navigation";
import LoginPage from "./page";
import { useAdminAuth } from "@/contexts/AdminAuthContext";
import { ApiError } from "@/lib/api-error-handler";

// next/navigationのモック
jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
}));

// AdminAuthContextのモック
jest.mock("@/contexts/AdminAuthContext", () => ({
  useAdminAuth: jest.fn(),
}));

describe("LoginPage (Admin App)", () => {
  const mockPush = jest.fn();
  const mockLogin = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (useRouter as jest.Mock).mockReturnValue({
      push: mockPush,
    });
    (useAdminAuth as jest.Mock).mockReturnValue({
      login: mockLogin,
      isLoading: false,
    });
  });

  it("ログインフォームが正しく表示される", () => {
    render(<LoginPage />);

    expect(screen.getByLabelText(/メールアドレス/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/パスワード/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /ログイン/i })).toBeInTheDocument();
  });

  it("メールアドレスとパスワードを入力できる", async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i) as HTMLInputElement;
    const passwordInput = screen.getByLabelText(/パスワード/i) as HTMLInputElement;

    await user.type(emailInput, "admin@example.com");
    await user.type(passwordInput, "password123");

    expect(emailInput.value).toBe("admin@example.com");
    expect(passwordInput.value).toBe("password123");
  });

  it("ログイン成功時にダッシュボードへリダイレクトする", async () => {
    const user = userEvent.setup();
    mockLogin.mockResolvedValueOnce(undefined);

    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i);
    const passwordInput = screen.getByLabelText(/パスワード/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    await user.type(emailInput, "admin@example.com");
    await user.type(passwordInput, "password123");
    await user.click(loginButton);

    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalledWith("admin@example.com", "password123");
    });

    await waitFor(() => {
      expect(mockPush).toHaveBeenCalledWith("/dashboard");
    });
  });

  it("メールアドレスが未入力の場合、バリデーションエラーを表示する", async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    const passwordInput = screen.getByLabelText(/パスワード/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    await user.type(passwordInput, "password123");
    await user.click(loginButton);

    expect(await screen.findByText(/メールアドレスを入力してください/i)).toBeInTheDocument();
    expect(mockLogin).not.toHaveBeenCalled();
  });

  it("パスワードが未入力の場合、バリデーションエラーを表示する", async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    await user.type(emailInput, "admin@example.com");
    await user.click(loginButton);

    expect(await screen.findByText(/パスワードを入力してください/i)).toBeInTheDocument();
    expect(mockLogin).not.toHaveBeenCalled();
  });

  it("メールアドレスの形式が不正な場合、バリデーションエラーを表示する", async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i);
    const passwordInput = screen.getByLabelText(/パスワード/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    await user.type(emailInput, "invalid-email");
    await user.type(passwordInput, "password123");
    await user.click(loginButton);

    expect(
      await screen.findByText(/正しいメールアドレスの形式で入力してください/i),
    ).toBeInTheDocument();
    expect(mockLogin).not.toHaveBeenCalled();
  });

  it("パスワードが8文字未満の場合、バリデーションエラーを表示する", async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i);
    const passwordInput = screen.getByLabelText(/パスワード/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    await user.type(emailInput, "admin@example.com");
    await user.type(passwordInput, "pass");
    await user.click(loginButton);

    expect(await screen.findByText(/パスワードは8文字以上で入力してください/i)).toBeInTheDocument();
    expect(mockLogin).not.toHaveBeenCalled();
  });

  it("ログイン失敗時（認証情報不正）にエラーメッセージを表示する", async () => {
    const user = userEvent.setup();
    mockLogin.mockRejectedValueOnce(
      new ApiError(
        "AUTH.INVALID_CREDENTIALS",
        "メールアドレスまたはパスワードが正しくありません",
        401,
      ),
    );

    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i);
    const passwordInput = screen.getByLabelText(/パスワード/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    await user.type(emailInput, "admin@example.com");
    await user.type(passwordInput, "wrongpassword");
    await user.click(loginButton);

    expect(
      await screen.findByText(/メールアドレスまたはパスワードが正しくありません/i),
    ).toBeInTheDocument();
    expect(mockPush).not.toHaveBeenCalled();
  });

  it("ログイン失敗時（アカウント無効化）にエラーメッセージを表示する", async () => {
    const user = userEvent.setup();
    mockLogin.mockRejectedValueOnce(
      new ApiError("AUTH.ACCOUNT_DISABLED", "アカウントが無効化されています", 403),
    );

    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i);
    const passwordInput = screen.getByLabelText(/パスワード/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    await user.type(emailInput, "admin@example.com");
    await user.type(passwordInput, "password123");
    await user.click(loginButton);

    expect(await screen.findByText(/アカウントが無効化されています/i)).toBeInTheDocument();
    expect(mockPush).not.toHaveBeenCalled();
  });

  it("ログイン処理中はボタンが無効化される", async () => {
    (useAdminAuth as jest.Mock).mockReturnValue({
      login: mockLogin,
      isLoading: true,
    });

    render(<LoginPage />);

    const loginButton = screen.getByRole("button", { name: /処理中/i });
    expect(loginButton).toBeDisabled();
  });

  it("バリデーションエラーをクリアできる", async () => {
    const user = userEvent.setup();
    render(<LoginPage />);

    const emailInput = screen.getByLabelText(/メールアドレス/i);
    const loginButton = screen.getByRole("button", { name: /ログイン/i });

    // エラーを表示
    await user.click(loginButton);
    expect(await screen.findByText(/メールアドレスを入力してください/i)).toBeInTheDocument();

    // 入力するとエラーがクリアされる
    await user.type(emailInput, "admin@example.com");
    await waitFor(() => {
      expect(screen.queryByText(/メールアドレスを入力してください/i)).not.toBeInTheDocument();
    });
  });
});
