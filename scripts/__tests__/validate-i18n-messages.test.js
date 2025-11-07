/**
 * validate-i18n-messages.js のUnit Test
 *
 * REQ-9.1: 翻訳ファイル構造の検証
 * REQ-9.2: 必須キーの存在確認
 * REQ-9.7: 検証スクリプトのUnit Test
 */

const fs = require('fs');
const path = require('path');

// モックテストディレクトリ
const TEST_DIR = path.join(__dirname, 'fixtures', 'i18n-messages');
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

describe('validate-i18n-messages.js', () => {
  beforeEach(() => {
    cleanupTestDir();
    fs.mkdirSync(TEST_DIR, { recursive: true });
  });

  afterEach(() => {
    cleanupTestDir();
  });

  describe('構造検証', () => {
    test('有効な翻訳ファイル構造でエラーなし', () => {
      // 有効な翻訳ファイルを作成
      const validContent = {
        errors: {
          network: {
            timeout: 'リクエストがタイムアウトしました。',
            connection: 'ネットワーク接続に問題が発生しました。',
            unknown: '予期しないエラーが発生しました。',
          },
          boundary: {
            title: 'エラーが発生しました',
            retry: '再試行',
            home: 'ホームに戻る',
            status: 'ステータスコード',
            requestId: 'Request ID',
            networkError: 'ネットワークエラー',
            timeout: 'タイムアウト',
            connectionError: '接続エラー',
            retryableMessage: 'このエラーは再試行可能です。',
          },
          validation: {
            title: '入力エラー',
          },
          global: {
            title: '予期しないエラーが発生しました',
            retry: '再試行',
            errorId: 'Error ID',
            contactMessage: 'お問い合わせの際は、このIDをお伝えください',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', validContent);
      createTestTranslationFile('user-app', 'en', validContent);

      // validate-i18n-messages.js をインポート（実装後）
      const { validateMessageStructure } = require('../validate-i18n-messages');

      // エラーなしで検証が成功することを期待
      expect(() => {
        validateMessageStructure(path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'));
      }).not.toThrow();
    });

    test('必須キー不足時にエラーをスロー（validateRequiredKeysで検証）', () => {
      // errors.network.timeout が不足している翻訳ファイル
      const invalidContent = {
        errors: {
          network: {
            connection: 'ネットワーク接続に問題が発生しました。',
            unknown: '予期しないエラーが発生しました。',
            // timeout が不足
          },
          boundary: {
            title: 'エラーが発生しました',
            retry: '再試行',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', invalidContent);

      const { validateRequiredKeys } = require('../validate-i18n-messages');

      // 必須キー不足でエラーがスローされることを期待
      const requiredKeys = ['errors.network.timeout'];
      expect(() => {
        validateRequiredKeys(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          requiredKeys
        );
      }).toThrow(/missing required keys/i);
    });

    test('ネストレベル不正時にエラーをスロー', () => {
      // ネストが深すぎる翻訳ファイル（4階層以上）
      const invalidContent = {
        errors: {
          network: {
            deep: {
              nested: {
                value: 'これは深すぎるネスト',
              },
            },
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', invalidContent);

      const { validateMessageStructure } = require('../validate-i18n-messages');

      // ネストレベル不正でエラーがスローされることを期待
      expect(() => {
        validateMessageStructure(path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'));
      }).toThrow(/nesting level/i);
    });

    test('翻訳値が文字列でない場合にエラーをスロー', () => {
      // 翻訳値が数値の場合
      const invalidContent = {
        errors: {
          network: {
            timeout: 123, // 文字列ではなく数値
            connection: 'ネットワーク接続に問題が発生しました。',
            unknown: '予期しないエラーが発生しました。',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', invalidContent);

      const { validateMessageStructure } = require('../validate-i18n-messages');

      // 翻訳値が文字列でない場合にエラーがスローされることを期待
      expect(() => {
        validateMessageStructure(path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'));
      }).toThrow(/must be a string/i);
    });

    test('JSONパースエラー時に適切なエラーメッセージをスロー', () => {
      // 不正なJSON形式のファイルを作成
      const dir = path.join(FRONTEND_DIR, 'user-app', 'messages');
      fs.mkdirSync(dir, { recursive: true });
      fs.writeFileSync(path.join(dir, 'ja.json'), '{ invalid json }');

      const { validateMessageStructure } = require('../validate-i18n-messages');

      // JSONパースエラーで適切なエラーがスローされることを期待
      expect(() => {
        validateMessageStructure(path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'));
      }).toThrow(/JSON parse error/i);
    });

    test('ファイルが存在しない場合にエラーをスロー', () => {
      const { validateMessageStructure } = require('../validate-i18n-messages');

      // 存在しないファイルパスでエラーがスローされることを期待
      expect(() => {
        validateMessageStructure(path.join(FRONTEND_DIR, 'user-app', 'messages', 'nonexistent.json'));
      }).toThrow(/file not found/i);
    });
  });

  describe('必須キー検証', () => {
    const requiredKeys = [
      'errors.network.timeout',
      'errors.network.connection',
      'errors.network.unknown',
      'errors.boundary.title',
      'errors.boundary.retry',
      'errors.boundary.home',
      'errors.boundary.status',
      'errors.boundary.requestId',
      'errors.boundary.networkError',
      'errors.boundary.timeout',
      'errors.boundary.connectionError',
      'errors.boundary.retryableMessage',
      'errors.validation.title',
      'errors.global.title',
      'errors.global.retry',
      'errors.global.errorId',
      'errors.global.contactMessage',
    ];

    test('全ての必須キーが存在することを確認', () => {
      const validContent = {
        errors: {
          network: {
            timeout: 'Timeout message',
            connection: 'Connection message',
            unknown: 'Unknown message',
          },
          boundary: {
            title: 'Title',
            retry: 'Retry',
            home: 'Home',
            status: 'Status',
            requestId: 'Request ID',
            networkError: 'Network Error',
            timeout: 'Timeout',
            connectionError: 'Connection Error',
            retryableMessage: 'Retryable message',
          },
          validation: {
            title: 'Validation Title',
          },
          global: {
            title: 'Global Title',
            retry: 'Retry',
            errorId: 'Error ID',
            contactMessage: 'Contact message',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', validContent);

      const { validateRequiredKeys } = require('../validate-i18n-messages');

      // 全ての必須キーが存在することを確認
      expect(() => {
        validateRequiredKeys(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          requiredKeys
        );
      }).not.toThrow();
    });

    test('一部の必須キーが不足している場合にエラーをスロー', () => {
      const invalidContent = {
        errors: {
          network: {
            timeout: 'Timeout message',
            // connection と unknown が不足
          },
          boundary: {
            title: 'Title',
            // 他のキーが不足
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', invalidContent);

      const { validateRequiredKeys } = require('../validate-i18n-messages');

      // 必須キー不足でエラーがスローされることを期待
      expect(() => {
        validateRequiredKeys(
          path.join(FRONTEND_DIR, 'user-app', 'messages', 'ja.json'),
          requiredKeys
        );
      }).toThrow(/missing required keys/i);
    });
  });

  describe('複数ファイル検証', () => {
    test('user-appとadmin-appの両方のja.jsonとen.jsonを検証', () => {
      const validContent = {
        errors: {
          network: {
            timeout: 'Timeout message',
            connection: 'Connection message',
            unknown: 'Unknown message',
          },
          boundary: {
            title: 'Title',
            retry: 'Retry',
            home: 'Home',
            status: 'Status',
            requestId: 'Request ID',
            networkError: 'Network Error',
            timeout: 'Timeout',
            connectionError: 'Connection Error',
            retryableMessage: 'Retryable message',
          },
          validation: {
            title: 'Validation Title',
          },
          global: {
            title: 'Global Title',
            retry: 'Retry',
            errorId: 'Error ID',
            contactMessage: 'Contact message',
          },
        },
      };

      createTestTranslationFile('user-app', 'ja', validContent);
      createTestTranslationFile('user-app', 'en', validContent);
      createTestTranslationFile('admin-app', 'ja', validContent);
      createTestTranslationFile('admin-app', 'en', validContent);

      const { validateAllMessageFiles } = require('../validate-i18n-messages');

      // 全てのファイルでエラーなしを期待
      expect(() => {
        validateAllMessageFiles(FRONTEND_DIR);
      }).not.toThrow();
    });
  });
});
