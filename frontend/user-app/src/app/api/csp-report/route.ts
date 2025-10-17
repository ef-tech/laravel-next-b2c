import { NextRequest, NextResponse } from "next/server";

/**
 * CSP違反レポートを受信するAPIエンドポイント
 *
 * ブラウザがContent Security Policy違反を検出した際に送信されるレポートを受信し、
 * 開発環境ではコンソールに出力、本番環境では外部監視サービスに転送する準備をする。
 *
 * @param request - Next.js Request オブジェクト
 * @returns 204 No Content レスポンス、またはエラーレスポンス
 */
export async function POST(request: NextRequest): Promise<NextResponse> {
  try {
    // Content-Type検証
    const contentType = request.headers.get("content-type");
    if (contentType !== "application/csp-report") {
      return NextResponse.json({ error: "Invalid Content-Type" }, { status: 400 });
    }

    // リクエストボディ解析
    const body = await request.json();

    // CSPレポート検証
    if (!body["csp-report"]) {
      return NextResponse.json({ error: "Empty CSP report" }, { status: 400 });
    }

    const cspReport = body["csp-report"];

    // 開発環境ではコンソールに出力
    if (process.env.NODE_ENV === "development") {
      const userAgent = request.headers.get("user-agent") || "unknown";
      const ip = request.headers.get("x-forwarded-for") || "unknown";

      console.warn("CSP Violation Report:", {
        blockedUri: cspReport["blocked-uri"],
        violatedDirective: cspReport["violated-directive"],
        originalPolicy: cspReport["original-policy"],
        documentUri: cspReport["document-uri"],
        referrer: cspReport["referrer"],
        sourceFile: cspReport["source-file"],
        lineNumber: cspReport["line-number"],
        columnNumber: cspReport["column-number"],
        statusCode: cspReport["status-code"],
        "User-Agent": userAgent,
        IP: ip,
        timestamp: new Date().toISOString(),
      });
    }

    // 本番環境では外部監視サービスに転送（将来実装）
    // if (process.env.NODE_ENV === 'production' && process.env.CSP_REPORT_URL) {
    //   await fetch(process.env.CSP_REPORT_URL, {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify(cspReport),
    //   });
    // }

    // 204 No Content を返却
    return new NextResponse(null, { status: 204 });
  } catch (error) {
    console.error("Error processing CSP report:", error);
    return NextResponse.json({ error: "Internal Server Error" }, { status: 500 });
  }
}
