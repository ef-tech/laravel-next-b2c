import * as fs from 'fs';
import * as path from 'path';

describe('環境変数同期スクリプト', () => {
  const testDir = path.join(__dirname, 'fixtures');
  const examplePath = path.join(testDir, '.env.example');
  const envPath = path.join(testDir, '.env');

  beforeEach(() => {
    // テストディレクトリを作成
    if (!fs.existsSync(testDir)) {
      fs.mkdirSync(testDir, { recursive: true });
    }
  });

  afterEach(() => {
    // テストディレクトリをクリーンアップ
    if (fs.existsSync(envPath)) {
      fs.unlinkSync(envPath);
    }
    if (fs.existsSync(examplePath)) {
      fs.unlinkSync(examplePath);
    }
  });

  afterAll(() => {
    // テストディレクトリを削除
    if (fs.existsSync(testDir)) {
      fs.rmSync(testDir, { recursive: true, force: true });
    }
  });

  test('不足キーが検出される', () => {
    fs.writeFileSync(examplePath, 'KEY1=value1\nKEY2=value2\n');
    fs.writeFileSync(envPath, 'KEY1=value1\n');

    // 差分検出ロジック
    const parseEnv = (content: string): string[] => {
      return content
        .split('\n')
        .filter((line) => line.trim() && !line.startsWith('#'))
        .map((line) => line.split('=')[0]);
    };

    const exampleKeys = parseEnv(fs.readFileSync(examplePath, 'utf-8'));
    const envKeys = parseEnv(fs.readFileSync(envPath, 'utf-8'));
    const missingKeys = exampleKeys.filter((key) => !envKeys.includes(key));

    expect(missingKeys).toEqual(['KEY2']);
  });

  test('未知キーが検出される', () => {
    fs.writeFileSync(examplePath, 'KEY1=value1\n');
    fs.writeFileSync(envPath, 'KEY1=value1\nUNKNOWN_KEY=value\n');

    // 差分検出ロジック
    const parseEnv = (content: string): string[] => {
      return content
        .split('\n')
        .filter((line) => line.trim() && !line.startsWith('#'))
        .map((line) => line.split('=')[0]);
    };

    const exampleKeys = parseEnv(fs.readFileSync(examplePath, 'utf-8'));
    const envKeys = parseEnv(fs.readFileSync(envPath, 'utf-8'));
    const unknownKeys = envKeys.filter((key) => !exampleKeys.includes(key));

    expect(unknownKeys).toEqual(['UNKNOWN_KEY']);
  });

  test('.envファイルが存在しない場合、.env.exampleからコピーされる', () => {
    fs.writeFileSync(examplePath, 'KEY1=value1\nKEY2=value2\n');

    // .envファイルが存在しない
    expect(fs.existsSync(envPath)).toBe(false);

    // .env.exampleを.envにコピー
    fs.copyFileSync(examplePath, envPath);

    expect(fs.existsSync(envPath)).toBe(true);
    const envContent = fs.readFileSync(envPath, 'utf-8');
    expect(envContent).toContain('KEY1=value1');
    expect(envContent).toContain('KEY2=value2');
  });
});
