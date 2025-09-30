#!/bin/bash

# PHPUnit to Pest conversion script
# Usage: bash scripts/convert-phpunit-to-pest.sh tests/Feature/ExampleTest.php

set -e

if [ -z "$1" ]; then
    echo "⚠️  Usage: bash scripts/convert-phpunit-to-pest.sh <file_path>"
    exit 1
fi

FILE="$1"

if [ ! -f "$FILE" ]; then
    echo "⚠️  File not found: $FILE"
    exit 1
fi

# Create backup
BACKUP="${FILE}.bak"
cp "$FILE" "$BACKUP"
echo "✅ Backup created: $BACKUP"

# Perform basic conversions
# Note: Using -i.bak for cross-platform compatibility (works on both macOS and Linux)
# 1. Remove namespace Tests declaration
sed -i.bak '/^namespace Tests;/d' "$FILE"

# 2. Remove use Tests\TestCase
sed -i.bak '/^use Tests\\TestCase;/d' "$FILE"

# 3. Remove class definition (simple pattern)
sed -i.bak '/^class [A-Za-z]* extends TestCase/d' "$FILE"
sed -i.bak '/^{$/d' "$FILE"

# 4. Convert test_ methods to it() syntax (basic pattern)
sed -i.bak 's/public function test_\(.*\)(/it('\''\1'\'', function () {/g' "$FILE"

# 5. Remove closing brace for class
sed -i.bak '/^}$/d' "$FILE"

# Remove additional .bak files created by sed
rm -f "$FILE.bak"

echo "✅ Basic conversion completed"
echo ""
echo "⚠️  Manual review required:"
echo "   1. Check $this->assert* calls"
echo "   2. Verify expect() syntax"
echo "   3. Review setUp() and tearDown() methods"
echo ""
echo "📝 Verify with: ./vendor/bin/pest $FILE"