import { GET } from "./route";

describe("GET /api/health", () => {
  it("should return 200 status code", async () => {
    const response = await GET();
    expect(response.status).toBe(200);
  });

  it('should return {"status": "ok"} JSON body', async () => {
    const response = await GET();
    const body = await response.json();
    expect(body).toEqual({ status: "ok" });
  });

  it("should be accessible without authentication", async () => {
    // ヘルスチェックエンドポイントは認証不要でアクセス可能
    const response = await GET();
    expect(response.status).toBe(200);
  });

  it("should work without external dependencies", async () => {
    // モックなしでテスト（外部依存なし）
    const response = await GET();
    expect(response.status).toBe(200);
  });

  it("should return the same response every time (idempotency)", async () => {
    // 冪等性確認
    const response1 = await GET();
    const body1 = await response1.json();

    const response2 = await GET();
    const body2 = await response2.json();

    expect(body1).toEqual(body2);
    expect(body1).toEqual({ status: "ok" });
  });
});
