# ============================================
# Base Stage - Node.js 20 Alpine ベースイメージ
# ============================================
FROM node:20-alpine AS base

# ============================================
# Deps Stage - 依存関係インストール
# ============================================
FROM base AS deps

# libc6-compat: Node.js native modulesの互換性確保
RUN apk add --no-cache libc6-compat

WORKDIR /app

# モノレポ対応: ルートとアプリケーション両方のpackage.jsonをコピー
COPY package.json package-lock.json* ./
COPY frontend/admin-app/package.json ./frontend/admin-app/

# 依存関係インストール（npm ci: package-lock.json厳密バージョン固定）
RUN npm ci

# ============================================
# Builder Stage - Next.jsビルド
# ============================================
FROM base AS builder

WORKDIR /app

# depsステージからnode_modulesをコピー
COPY --from=deps /app/node_modules ./node_modules

# アプリケーションソースコードをコピー
COPY . .

# Next.jsテレメトリ無効化（プライバシー保護、ビルド高速化）
ENV NEXT_TELEMETRY_DISABLED=1

# Next.jsビルド実行（workspace指定でadmin-appのみビルド）
RUN npm run build --workspace=frontend/admin-app

# ============================================
# Runner Stage - 本番環境実行
# ============================================
FROM base AS runner

WORKDIR /app

# 本番環境設定
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

# nextjsユーザー作成（最小権限実行、UID 1001）
RUN addgroup --system --gid 1001 nodejs && \
    adduser --system --uid 1001 nextjs

# standaloneビルド成果物をコピー
COPY --from=builder /app/frontend/admin-app/.next/standalone ./
COPY --from=builder /app/frontend/admin-app/.next/static ./frontend/admin-app/.next/static
COPY --from=builder /app/frontend/admin-app/public ./frontend/admin-app/public

# nextjsユーザーに所有権変更
RUN chown -R nextjs:nodejs /app

# nextjsユーザーに切り替え
USER nextjs

# ポート3001公開（Admin App専用ポート）
EXPOSE 3001

# 環境変数設定（ポート、ホスト名）
ENV PORT=3001
ENV HOSTNAME="0.0.0.0"

# Next.jsサーバー起動
CMD ["node", "frontend/admin-app/server.js"]
