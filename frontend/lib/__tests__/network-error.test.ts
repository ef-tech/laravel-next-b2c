/**
 * NetworkError Unit Tests
 *
 * Task 9: NetworkError Unit Testsの実装
 * - Task 9.1: 後方互換性テスト実装（引数なしでgetDisplayMessage()を呼び出し）
 * - Task 9.2: i18n有効化時テスト実装（翻訳関数を渡す）
 * - Task 9.3: 100%カバレッジ達成
 *
 * Requirements:
 * - REQ-8.1: NetworkError unit tests with 100% coverage
 * - REQ-8.2: Backward compatibility verification
 * - REQ-10.4: Backward compatibility maintenance
 */

import { NetworkError } from '../network-error';

describe('NetworkError', () => {
  describe('fromFetchError factory method', () => {
    it('TypeError "Failed to fetch"から接続エラーを生成する', () => {
      const fetchError = new TypeError('Failed to fetch');
      const error = NetworkError.fromFetchError(fetchError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.name).toBe('NetworkError');
      expect(error.message).toBe('Network connection failed');
      expect(error.originalError).toBe(fetchError);
      expect(error.isRetryable).toBe(true);
    });

    it('AbortErrorからタイムアウトエラーを生成する', () => {
      const abortError = new Error('The operation was aborted');
      abortError.name = 'AbortError';
      const error = NetworkError.fromFetchError(abortError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.name).toBe('NetworkError');
      expect(error.message).toBe('Request timeout');
      expect(error.originalError).toBe(abortError);
      expect(error.isRetryable).toBe(true);
    });

    it('TimeoutErrorからタイムアウトエラーを生成する', () => {
      const timeoutError = new Error('Timeout');
      timeoutError.name = 'TimeoutError';
      const error = NetworkError.fromFetchError(timeoutError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.name).toBe('NetworkError');
      expect(error.message).toBe('Request timeout');
      expect(error.originalError).toBe(timeoutError);
      expect(error.isRetryable).toBe(true);
    });

    it('その他のエラーから汎用ネットワークエラーを生成する', () => {
      const genericError = new Error('Some network issue');
      const error = NetworkError.fromFetchError(genericError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.name).toBe('NetworkError');
      expect(error.message).toBe('Some network issue');
      expect(error.originalError).toBe(genericError);
      expect(error.isRetryable).toBe(false);
    });

    it('メッセージなしのエラーからデフォルトメッセージを生成する', () => {
      const emptyError = new Error('');
      emptyError.message = '';
      const error = NetworkError.fromFetchError(emptyError);

      expect(error).toBeInstanceOf(NetworkError);
      expect(error.message).toBe('Network error occurred');
      expect(error.isRetryable).toBe(false);
    });
  });

  describe('Error type detection methods', () => {
    describe('isTimeout()', () => {
      it('AbortErrorの場合trueを返す', () => {
        const abortError = new Error('Aborted');
        abortError.name = 'AbortError';
        const error = NetworkError.fromFetchError(abortError);

        expect(error.isTimeout()).toBe(true);
      });

      it('TimeoutErrorの場合trueを返す', () => {
        const timeoutError = new Error('Timeout');
        timeoutError.name = 'TimeoutError';
        const error = NetworkError.fromFetchError(timeoutError);

        expect(error.isTimeout()).toBe(true);
      });

      it('その他のエラーの場合falseを返す', () => {
        const genericError = new Error('Generic');
        const error = NetworkError.fromFetchError(genericError);

        expect(error.isTimeout()).toBe(false);
      });
    });

    describe('isConnectionError()', () => {
      it('TypeError "Failed to fetch"の場合trueを返す', () => {
        const fetchError = new TypeError('Failed to fetch');
        const error = NetworkError.fromFetchError(fetchError);

        expect(error.isConnectionError()).toBe(true);
      });

      it('その他のTypeErrorの場合falseを返す', () => {
        const typeError = new TypeError('Other error');
        const error = NetworkError.fromFetchError(typeError);

        expect(error.isConnectionError()).toBe(false);
      });

      it('非TypeErrorの場合falseを返す', () => {
        const genericError = new Error('Generic');
        const error = NetworkError.fromFetchError(genericError);

        expect(error.isConnectionError()).toBe(false);
      });
    });
  });

  describe('getDisplayMessage()', () => {
    /**
     * Task 9.1: 後方互換性テスト実装
     * - 引数なしでgetDisplayMessage()を呼び出し
     * - 日本語ハードコードメッセージが返却されることを確認
     * - タイムアウト、接続エラー、不明なエラーの全パターンをテスト
     */
    describe('Backward compatibility (no translation function)', () => {
      it('タイムアウトエラー（AbortError）で日本語メッセージを返す', () => {
        const abortError = new Error('Aborted');
        abortError.name = 'AbortError';
        const error = NetworkError.fromFetchError(abortError);

        expect(error.getDisplayMessage()).toBe(
          'リクエストがタイムアウトしました。しばらくしてから再度お試しください。'
        );
      });

      it('タイムアウトエラー（TimeoutError）で日本語メッセージを返す', () => {
        const timeoutError = new Error('Timeout');
        timeoutError.name = 'TimeoutError';
        const error = NetworkError.fromFetchError(timeoutError);

        expect(error.getDisplayMessage()).toBe(
          'リクエストがタイムアウトしました。しばらくしてから再度お試しください。'
        );
      });

      it('接続エラーで日本語メッセージを返す', () => {
        const fetchError = new TypeError('Failed to fetch');
        const error = NetworkError.fromFetchError(fetchError);

        expect(error.getDisplayMessage()).toBe(
          'ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。'
        );
      });

      it('不明なエラーで日本語メッセージを返す', () => {
        const unknownError = new Error('Unknown network issue');
        const error = NetworkError.fromFetchError(unknownError);

        expect(error.getDisplayMessage()).toBe(
          '予期しないエラーが発生しました。しばらくしてから再度お試しください。'
        );
      });

      it('undefined引数で日本語メッセージを返す（明示的なundefined）', () => {
        const abortError = new Error('Aborted');
        abortError.name = 'AbortError';
        const error = NetworkError.fromFetchError(abortError);

        expect(error.getDisplayMessage(undefined)).toBe(
          'リクエストがタイムアウトしました。しばらくしてから再度お試しください。'
        );
      });

      it('null引数で日本語メッセージを返す', () => {
        const fetchError = new TypeError('Failed to fetch');
        const error = NetworkError.fromFetchError(fetchError);

        expect(error.getDisplayMessage(null)).toBe(
          'ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。'
        );
      });
    });

    /**
     * Task 9.2: i18n有効化時のテスト実装
     * - モック翻訳関数を作成
     * - getDisplayMessage(mockT)で翻訳メッセージが返却されることを確認
     * - タイムアウト、接続エラー、不明なエラーの全パターンをテスト
     * - 翻訳キーの正確性を検証
     */
    describe('i18n-enabled (with translation function)', () => {
      const mockT = jest.fn((key: string) => {
        const translations: Record<string, string> = {
          'network.timeout': 'The request timed out. Please try again later.',
          'network.connection':
            'A network connection problem occurred. Please check your internet connection and try again.',
          'network.unknown': 'An unexpected error occurred. Please try again later.',
        };
        return translations[key] || key;
      });

      beforeEach(() => {
        mockT.mockClear();
      });

      it('タイムアウトエラー（AbortError）で翻訳メッセージを返す', () => {
        const abortError = new Error('Aborted');
        abortError.name = 'AbortError';
        const error = NetworkError.fromFetchError(abortError);

        const result = error.getDisplayMessage(mockT);

        expect(result).toBe('The request timed out. Please try again later.');
        expect(mockT).toHaveBeenCalledWith('network.timeout');
        expect(mockT).toHaveBeenCalledTimes(1);
      });

      it('タイムアウトエラー（TimeoutError）で翻訳メッセージを返す', () => {
        const timeoutError = new Error('Timeout');
        timeoutError.name = 'TimeoutError';
        const error = NetworkError.fromFetchError(timeoutError);

        const result = error.getDisplayMessage(mockT);

        expect(result).toBe('The request timed out. Please try again later.');
        expect(mockT).toHaveBeenCalledWith('network.timeout');
        expect(mockT).toHaveBeenCalledTimes(1);
      });

      it('接続エラーで翻訳メッセージを返す', () => {
        const fetchError = new TypeError('Failed to fetch');
        const error = NetworkError.fromFetchError(fetchError);

        const result = error.getDisplayMessage(mockT);

        expect(result).toBe(
          'A network connection problem occurred. Please check your internet connection and try again.'
        );
        expect(mockT).toHaveBeenCalledWith('network.connection');
        expect(mockT).toHaveBeenCalledTimes(1);
      });

      it('不明なエラーで翻訳メッセージを返す', () => {
        const unknownError = new Error('Unknown network issue');
        const error = NetworkError.fromFetchError(unknownError);

        const result = error.getDisplayMessage(mockT);

        expect(result).toBe('An unexpected error occurred. Please try again later.');
        expect(mockT).toHaveBeenCalledWith('network.unknown');
        expect(mockT).toHaveBeenCalledTimes(1);
      });

      it('翻訳関数が空文字列を返しても正常に動作する', () => {
        const emptyMockT = jest.fn(() => '');
        const abortError = new Error('Aborted');
        abortError.name = 'AbortError';
        const error = NetworkError.fromFetchError(abortError);

        const result = error.getDisplayMessage(emptyMockT);

        expect(result).toBe('');
        expect(emptyMockT).toHaveBeenCalledWith('network.timeout');
      });

      it('翻訳関数が翻訳キーをそのまま返しても正常に動作する', () => {
        const passthroughMockT = jest.fn((key: string) => key);
        const fetchError = new TypeError('Failed to fetch');
        const error = NetworkError.fromFetchError(fetchError);

        const result = error.getDisplayMessage(passthroughMockT);

        expect(result).toBe('network.connection');
        expect(passthroughMockT).toHaveBeenCalledWith('network.connection');
      });
    });
  });

  /**
   * Task 9.3: 100%カバレッジ達成のための追加テスト
   * - 全ブランチ（if文、switch文）をカバー
   * - エッジケース（null、undefined、空文字列）をテスト
   */
  describe('Edge cases and coverage completeness', () => {
    it('NetworkErrorのnameプロパティが正しく設定される', () => {
      const error = NetworkError.fromFetchError(new Error('Test'));
      expect(error.name).toBe('NetworkError');
    });

    it('originalErrorプロパティが元のエラーを保持する', () => {
      const originalError = new Error('Original');
      const error = NetworkError.fromFetchError(originalError);
      expect(error.originalError).toBe(originalError);
    });

    it('isRetryableプロパティがタイムアウトエラーでtrueになる', () => {
      const abortError = new Error('Aborted');
      abortError.name = 'AbortError';
      const error = NetworkError.fromFetchError(abortError);
      expect(error.isRetryable).toBe(true);
    });

    it('isRetryableプロパティが接続エラーでtrueになる', () => {
      const fetchError = new TypeError('Failed to fetch');
      const error = NetworkError.fromFetchError(fetchError);
      expect(error.isRetryable).toBe(true);
    });

    it('isRetryableプロパティが不明なエラーでfalseになる', () => {
      const unknownError = new Error('Unknown');
      const error = NetworkError.fromFetchError(unknownError);
      expect(error.isRetryable).toBe(false);
    });

    it('Error.captureStackTraceが呼び出される（利用可能な環境）', () => {
      // V8環境（Node.js）ではError.captureStackTraceが存在する
      const originalCaptureStackTrace = Error.captureStackTrace;
      const mockCaptureStackTrace = jest.fn();
      Error.captureStackTrace = mockCaptureStackTrace;

      NetworkError.fromFetchError(new Error('Test'));

      expect(mockCaptureStackTrace).toHaveBeenCalled();

      // 元に戻す
      Error.captureStackTrace = originalCaptureStackTrace;
    });
  });
});
