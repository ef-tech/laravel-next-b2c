#!/bin/bash
set -euo pipefail

# スクリプトの絶対パスを取得し、Laravel APIルートディレクトリに移動
SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
LARAVEL_API_ROOT="$SCRIPT_DIR/.."
cd "$LARAVEL_API_ROOT"

echo "=========================================="
echo "Timestamp Format Migration Script"
echo "=========================================="
echo "Working directory: $(pwd)"
echo ""

# 1. 対象ファイル検出
echo "🔍 Detecting target files..."
echo ""

echo "--- Pattern 1: Manual format() ---"
rg "format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)" --type php --files-with-matches || true

echo ""
echo "--- Pattern 2: toIso8601String() without utc() ---"
rg "toIso8601String\(\)" --type php -n | grep -v "utc()->toIso8601String" | head -30 || true

# 2. Perl一括置換実行
echo ""
echo "🔧 Executing Perl replacements..."
echo ""

# Pattern 1: now()->format() 置換
find . -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe "s/now\(\)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/now()->utc()->toIso8601String()/g" {} +
echo "✅ Pattern 1 replaced: now()->format('Y-m-d\TH:i:s\Z')"

# Pattern 2: Carbon::now()->format() 置換
find . -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe "s/Carbon::now\(\)->format\(['\"]Y-m-d\\\\TH:i:s\\\\Z['\"]\)/Carbon::now()->utc()->toIso8601String()/g" {} +
echo "✅ Pattern 2 replaced: Carbon::now()->format('Y-m-d\TH:i:s\Z')"

# Pattern 3: toIso8601String()の前にutc()追加（既存のutc()がない場合のみ）
# 注意: 既に utc()->toIso8601String() になっている箇所は置換しない
find . -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe 's/(?<!utc\(\)->)toIso8601String\(\)/utc()->toIso8601String()/g' {} +
echo "✅ Pattern 3 replaced: added utc() before toIso8601String()"

# Pattern 4: 変数->format() 置換
find . -type f -name "*.php" -not -path "*/vendor/*" -not -path "*/docs/*" \
    -exec perl -i -pe 's/(\$[a-zA-Z_][a-zA-Z0-9_]*)->format\(['"'"'"]Y-m-d\\TH:i:s\\Z['"'"'"]\)/$1->utc()->toIso8601String()/g' {} +
echo "✅ Pattern 4 replaced: \$variable->format('Y-m-d\TH:i:s\Z')"

# 3. 手動確認が必要な箇所を検出
echo ""
echo "⚠️  Manual review required for DateTime/DateTimeImmutable:"
rg "DateTime(Immutable)?.*format\(" --type php || echo "None found."

# 4. 変更ファイル一覧
echo ""
echo "📄 Changed files:"
git diff --name-only

echo ""
echo "=========================================="
echo "✅ Migration script completed"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Review changes: git diff"
echo "2. Run PHPStan: ./vendor/bin/phpstan analyse"
echo "3. Run Pint: ./vendor/bin/pint"
echo "4. Run tests: ./vendor/bin/pest"
echo "5. If issues, rollback: git reset --hard backup/before-timestamp-migration"
