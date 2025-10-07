/**
 * Admin App ヘルスチェックエンドポイント
 *
 * Dockerヘルスチェック用のエンドポイント。
 * Next.jsサーバーの起動完了状態を確認するために使用される。
 *
 * @returns HTTPステータス200と {"status": "ok"} を返す
 */
export async function GET(): Promise<Response> {
  return new Response(JSON.stringify({ status: "ok" }), {
    status: 200,
    headers: {
      "Content-Type": "application/json",
    },
  });
}
