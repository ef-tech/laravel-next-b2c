/**
 * validate-i18n-keys.js のUnit Test
 *
 * REQ-9.3: 翻訳ファイル間のキー整合性検証
 * REQ-9.7: 検証スクリプトのUnit Test
 */

const fs = require('fs');
const path = require('path');

// モックテストディレクトリ
const TEST_DIR = path.join(__dirname, 'fixtures', 'i18n-keys');
const FRONTEND_DIR = path.join(TEST_DIR, 'frontend');

/**
 * テスト用の翻訳ファイルを作成するヘルパー関数
 */
function createTestTranslationFile(appName, locale, content) {
  const dir = path.join(FRONTEND_DIR, appName, 'messages');
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(path.join(dir, `${locale}.json`), JSON.stringify(content, null, 2));
}

/**
 * テストディレクトリをクリーンアップ
 */
function cleanupTestDir() {
  if (fs.existsSync(TEST_DIR)) {
    fs.rmSync(TEST_DIR, { recursive: true, force: true });
  }
}

describe('validate-i18n-keys.js', () => {
  beforeEach(() => {
    cleanupTestDir();
    fs.mkdirSync(TEST_DIR, { recursive: true });
  });

  afterEach(() => {
    cleanupTestDir();
  });

  describe('キー整合性検証', () => {
    test('ja.jsonとen.jsonのキーが完全に一致する場合、エラーなし', () => {
      const content = {
        errors: {
          network: {
            timeout: 'タイムアウト',
            connection: '接続エラー',
            unknown: '不明なエラー',
          },
          boundary: {
            title: 'エラー',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', content);
      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
            connection: 'Connection Error',
            unknown: 'Unknown Error',
          },
          boundary: {
            title: 'Error',
          },
        },
      });

      const { validateKeyConsistency } = require('../validate-i18n-keys');

      // キーが一致しているのでエラーなし
      expect(() => {
        validateKeyConsistency(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
        );
      }).not.toThrow();
    });

    test('ja.jsonにのみ存在するキーがある場合にエラーをスロー', () => {
      createTestTranslationFile('user-app', 'ja', {
        errors: {
          network: {
            timeout: 'タイムアウト',
            connection: '接続エラー',
            unknown: '不明なエラー',
          },
        },
      });

      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
            // connection が不足
            unknown: 'Unknown Error',
          },
        },
      });

      const { validateKeyConsistency } = require('../validate-i18n-keys');

      // ja.jsonにのみ存在するキーがあるのでエラー
      expect(() => {
        validateKeyConsistency(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
        );
      }).toThrow(/only in ja\.json/i);
    });

    test('en.jsonにのみ存在するキーがある場合にエラーをスロー', () => {
      createTestTranslationFile('user-app', 'ja', {
        errors: {
          network: {
            timeout: 'タイムアウト',
            unknown: '不明なエラー',
          },
        },
      });

      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
            connection: 'Connection Error', // ja.jsonには存在しない
            unknown: 'Unknown Error',
          },
        },
      });

      const { validateKeyConsistency } = require('../validate-i18n-keys');

      // en.jsonにのみ存在するキーがあるのでエラー
      expect(() => {
        validateKeyConsistency(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
        );
      }).toThrow(/only in en\.json/i);
    });

    test('両方のファイルに異なるキーが存在する場合にエラーをスロー', () => {
      createTestTranslationFile('user-app', 'ja', {
        errors: {
          network: {
            timeout: 'タイムアウト',
            jaOnly: '日本語のみ',
          },
        },
      });

      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
            enOnly: 'English only',
          },
        },
      });

      const { validateKeyConsistency } = require('../validate-i18n-keys');

      // 両方にのみ存在するキーがあるのでエラー
      expect(() => {
        validateKeyConsistency(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
        );
      }).toThrow(/(only in ja\.json|only in en\.json)/i);
    });
  });

  describe('キー数の検証', () => {
    test('キー数が一致する場合、エラーなし', () => {
      const jaContent = {
        errors: {
          network: {
            timeout: 'タイムアウト',
            connection: '接続エラー',
            unknown: '不明なエラー',
          },
        },
      };

      const enContent = {
        errors: {
          network: {
            timeout: 'Timeout',
            connection: 'Connection Error',
            unknown: 'Unknown Error',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', jaContent);
      createTestTranslationFile('user-app', 'en', enContent);

      const { validateKeyCount } = require('../validate-i18n-keys');

      // キー数が一致しているのでエラーなし
      expect(() => {
        validateKeyCount(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
        );
      }).not.toThrow();
    });

    test('キー数が一致しない場合にエラーをスロー', () => {
      createTestTranslationFile('user-app', 'ja', {
        errors: {
          network: {
            timeout: 'タイムアウト',
            connection: '接続エラー',
            unknown: '不明なエラー',
          },
        },
      });

      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
            connection: 'Connection Error',
            // unknown が不足（キー数が異なる）
          },
        },
      });

      const { validateKeyCount } = require('../validate-i18n-keys');

      // キー数が一致しないのでエラー
      expect(() => {
        validateKeyCount(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
        );
      }).toThrow(/key count mismatch/i);
    });
  });

  describe('不足キーのレポート出力', () => {
    test('不足キーのリストが正しく返される', () => {
      createTestTranslationFile('user-app', 'ja', {
        errors: {
          network: {
            timeout: 'タイムアウト',
            connection: '接続エラー',
            unknown: '不明なエラー',
          },
        },
      });

      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
            // connection と unknown が不足
          },
        },
      });

      const { getMissingKeys } = require('../validate-i18n-keys');

      const result = getMissingKeys(
        path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
        path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
      );

      // ja.jsonにのみ存在するキー
      expect(result.onlyInFirst).toContain('errors.network.connection');
      expect(result.onlyInFirst).toContain('errors.network.unknown');

      // en.jsonにのみ存在するキー
      expect(result.onlyInSecond).toHaveLength(0);
    });

    test('不足キーがない場合、空配列が返される', () => {
      const content = {
        errors: {
          network: {
            timeout: 'タイムアウト',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', content);
      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
          },
        },
      });

      const { getMissingKeys } = require('../validate-i18n-keys');

      const result = getMissingKeys(
        path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
        path.join(FRONTEND_DIR, 'user-app', 'messages', 'en.json')
      );

      expect(result.onlyInFirst).toHaveLength(0);
      expect(result.onlyInSecond).toHaveLength(0);
    });
  });

  describe('複数アプリの検証', () => {
    test('user-appとadmin-appの両方で整合性が取れている場合、エラーなし', () => {
      const content = {
        errors: {
          network: {
            timeout: 'タイムアウト',
          },
        },
      };

      // user-app
      createTestTranslationFile('user-app', 'ja', content);
      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
          },
        },
      });

      // admin-app
      createTestTranslationFile('admin-app', 'ja', content);
      createTestTranslationFile('admin-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
          },
        },
      });

      const { validateAllKeyConsistency } = require('../validate-i18n-keys');

      // 全てのアプリで整合性が取れているのでエラーなし
      expect(() => {
        validateAllKeyConsistency(FRONTEND_DIR);
      }).not.toThrow();
    });

    test('いずれかのアプリで整合性エラーがある場合にエラーをスロー', () => {
      // user-app: OK
      createTestTranslationFile('user-app', 'ja', {
        errors: {
          network: {
            timeout: 'タイムアウト',
          },
        },
      });
      createTestTranslationFile('user-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
          },
        },
      });

      // admin-app: NG（en.jsonにキーが不足）
      createTestTranslationFile('admin-app', 'ja', {
        errors: {
          network: {
            timeout: 'タイムアウト',
            connection: '接続エラー',
          },
        },
      });
      createTestTranslationFile('admin-app', 'en', {
        errors: {
          network: {
            timeout: 'Timeout',
            // connection が不足
          },
        },
      });

      const { validateAllKeyConsistency } = require('../validate-i18n-keys');

      // admin-appでエラーがあるので全体でエラー
      expect(() => {
        validateAllKeyConsistency(FRONTEND_DIR);
      }).toThrow(/admin-app/i);
    });
  });
});
