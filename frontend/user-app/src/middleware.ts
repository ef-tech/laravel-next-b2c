import { NextRequest, NextResponse } from "next/server";

/**
 * 認証が必要なパスのリスト
 */
const PROTECTED_PATHS = ["/profile"];

/**
 * 認証ミドルウェア
 *
 * User App の認証ルート保護を提供します。
 * - 認証が必要なページへのアクセス制御
 * - 未認証時にログイン画面へリダイレクト
 * - トークンの有無でアクセス制御を判定
 */
export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // 静的ファイルとAPIルートはスキップ
  if (pathname.startsWith("/_next") || pathname.startsWith("/api") || pathname === "/favicon.ico") {
    return NextResponse.next();
  }

  // 認証が必要なパスかチェック
  const isProtectedPath = PROTECTED_PATHS.some((path) => pathname.startsWith(path));

  if (isProtectedPath) {
    // Cookieからトークンを取得
    const token = request.cookies.get("user_token")?.value;

    // トークンがない場合はログイン画面へリダイレクト
    if (!token) {
      const loginUrl = new URL("/login", request.url);
      return NextResponse.redirect(loginUrl);
    }
  }

  return NextResponse.next();
}

/**
 * ミドルウェア設定
 */
export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    "/((?!_next/static|_next/image|favicon.ico).*)",
  ],
};
