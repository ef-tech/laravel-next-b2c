import type { NextConfig } from "next";
import path from "path";

const nextConfig: NextConfig = {
  /* config options here */
  experimental: {
    // Monorepo環境でのNext.jsビルド警告を解消するために設定
    // "Warning: Next.js inferred your workspace root, but it may not be correct..."を回避
    outputFileTracingRoot: path.join(__dirname, "../../"),
  },
};

export default nextConfig;
