"use client";

/**
 * Simple Error Test Page
 * シンプルなエラーテスト - useRef/useStateを使わず直接throw
 */

import { ApiError } from "@/lib/api-error";
import type { RFC7807Problem } from "@/types/errors";

// Force dynamic rendering
export const dynamic = "force-dynamic";

export default function SimpleErrorTestPage() {
  // 直接ApiErrorを作成してthrow
  const problem: RFC7807Problem = {
    type: "http://localhost/errors/test",
    title: "Simple Test Error",
    status: 400,
    detail: "This is a simple test error thrown directly",
    error_code: "TEST-001",
    trace_id: "test-trace-id-12345",
    instance: "/simple-error-test",
    timestamp: new Date().toISOString(),
  };

  const error = new ApiError(problem);

  console.error("=== Simple Error Test: Throwing error directly ===");
  console.error("Error properties:", {
    title: error.title,
    status: error.status,
    requestId: error.requestId,
  });
  console.error("Error instanceof ApiError:", error instanceof ApiError);

  // 直接throw（useRef/useStateなし）
  throw error;
}
