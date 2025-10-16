#!/usr/bin/env tsx
import * as fs from 'fs';
import * as path from 'path';
import { parse } from 'dotenv';
import { Command } from 'commander';

const program = new Command();

program
  .option('--check', '.env.example と .env の差分をチェック（書き込みなし）')
  .option('--write', '.env.example の新規キーを .env に追加');

program.parse(process.argv);
const options = program.opts();

interface EnvFiles {
  examplePath: string;
  envPath: string;
}

const ENV_FILES: EnvFiles[] = [
  { examplePath: '.env.example', envPath: '.env' },
  {
    examplePath: 'backend/laravel-api/.env.example',
    envPath: 'backend/laravel-api/.env',
  },
  { examplePath: 'e2e/.env.example', envPath: 'e2e/.env' },
];

function parseEnvFile(filePath: string): Record<string, string> {
  if (!fs.existsSync(filePath)) {
    return {};
  }
  const content = fs.readFileSync(filePath, 'utf-8');
  return parse(content);
}

function checkDiff(
  example: Record<string, string>,
  env: Record<string, string>,
): {
  missing: string[];
  unknown: string[];
} {
  const exampleKeys = Object.keys(example);
  const envKeys = Object.keys(env);

  const missing = exampleKeys.filter((key) => !(key in env));
  const unknown = envKeys.filter((key) => !(key in example));

  return { missing, unknown };
}

function syncEnvFiles(examplePath: string, envPath: string): void {
  const example = parseEnvFile(examplePath);
  const env = parseEnvFile(envPath);

  const { missing, unknown } = checkDiff(example, env);

  console.log(`\n📝 ${examplePath} → ${envPath}`);

  if (missing.length === 0 && unknown.length === 0) {
    console.log('✅ 差分なし');
    return;
  }

  if (missing.length > 0) {
    console.log(`⚠️  不足キー (${missing.length}件):`);
    missing.forEach((key) => console.log(`  - ${key}`));
  }

  if (unknown.length > 0) {
    console.log(`⚠️  未知キー (${unknown.length}件):`);
    unknown.forEach((key) => console.log(`  - ${key}`));
    console.log(`   → .env.example への追加を検討してください`);
  }

  if (options.write && missing.length > 0) {
    // .env に不足キーを追加
    const envContent = fs.existsSync(envPath)
      ? fs.readFileSync(envPath, 'utf-8')
      : '';
    const newLines = missing.map((key) => `${key}=${example[key] || ''}`);
    const updatedContent = envContent + '\n' + newLines.join('\n') + '\n';
    fs.writeFileSync(envPath, updatedContent);
    console.log(`✅ ${missing.length}件のキーを ${envPath} に追加しました`);
  }
}

function main(): void {
  console.log('🔍 環境変数の同期チェックを開始します...');

  if (!options.check && !options.write) {
    console.error('❌ --check または --write オプションを指定してください');
    process.exit(1);
  }

  let hasErrors = false;

  ENV_FILES.forEach(({ examplePath, envPath }) => {
    if (!fs.existsSync(examplePath)) {
      console.log(`⚠️  ${examplePath} が存在しません。スキップします。`);
      return;
    }

    if (!fs.existsSync(envPath)) {
      if (options.write) {
        console.log(
          `📝 ${envPath} が存在しないため、${examplePath} からコピーします。`,
        );
        fs.copyFileSync(examplePath, envPath);
        console.log(`✅ ${envPath} を作成しました`);
      } else {
        console.log(
          `⚠️  ${envPath} が存在しません。--write オプションで作成できます。`,
        );
        hasErrors = true;
      }
      return;
    }

    syncEnvFiles(examplePath, envPath);
  });

  console.log('\n✅ 同期チェックが完了しました。');

  if (options.check && hasErrors) {
    process.exit(1);
  }
}

main();
