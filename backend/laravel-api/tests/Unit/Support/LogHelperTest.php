<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\LogHelper;
use Tests\TestCase;

/**
 * LogHelper Unit Tests
 *
 * 個人情報ハッシュ化機能のテスト
 */
final class LogHelperTest extends TestCase
{
    /**
     * LOG_HASH_SENSITIVE_DATA=false の場合、値をそのまま返す
     */
    public function test_returns_original_value_when_hashing_disabled(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => false]);

        // Act & Assert
        $this->assertSame(123, LogHelper::hashSensitiveData(123));
        $this->assertSame('test@example.com', LogHelper::hashSensitiveData('test@example.com'));
        $this->assertSame('192.168.1.1', LogHelper::hashSensitiveData('192.168.1.1'));
    }

    /**
     * LOG_HASH_SENSITIVE_DATA=true の場合、値をSHA-256でハッシュ化する
     */
    public function test_returns_hashed_value_when_hashing_enabled(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => true]);

        // Act
        $hashedUserId = LogHelper::hashSensitiveData(123);
        $hashedEmail = LogHelper::hashSensitiveData('test@example.com');
        $hashedIp = LogHelper::hashSensitiveData('192.168.1.1');

        // Assert
        $this->assertIsString($hashedUserId);
        $this->assertSame(64, strlen($hashedUserId)); // SHA-256は64文字の16進数文字列
        $this->assertNotSame('123', $hashedUserId);

        $this->assertIsString($hashedEmail);
        $this->assertSame(64, strlen($hashedEmail));
        $this->assertNotSame('test@example.com', $hashedEmail);

        $this->assertIsString($hashedIp);
        $this->assertSame(64, strlen($hashedIp));
        $this->assertNotSame('192.168.1.1', $hashedIp);
    }

    /**
     * 同じ値は同じハッシュ値になる（一貫性テスト）
     */
    public function test_same_value_produces_same_hash(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => true]);

        // Act
        $hash1 = LogHelper::hashSensitiveData(123);
        $hash2 = LogHelper::hashSensitiveData(123);

        // Assert
        $this->assertSame($hash1, $hash2);
    }

    /**
     * 異なる値は異なるハッシュ値になる
     */
    public function test_different_values_produce_different_hashes(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => true]);

        // Act
        $hash1 = LogHelper::hashSensitiveData(123);
        $hash2 = LogHelper::hashSensitiveData(456);

        // Assert
        $this->assertNotSame($hash1, $hash2);
    }

    /**
     * null値はnullのまま返される
     */
    public function test_null_values_return_null(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => true]);

        // Act & Assert
        $this->assertNull(LogHelper::hashSensitiveData(null));
    }

    /**
     * 配列の各要素がハッシュ化される
     */
    public function test_array_values_are_hashed(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => true]);

        // Act
        $result = LogHelper::hashSensitiveData([
            'user_id' => 123,
            'email' => 'test@example.com',
        ]);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertIsString($result['user_id']);
        $this->assertIsString($result['email']);
        $this->assertSame(64, strlen($result['user_id']));
        $this->assertSame(64, strlen($result['email']));
    }

    /**
     * ハッシュ化が無効の場合、配列もそのまま返される
     */
    public function test_array_values_not_hashed_when_disabled(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => false]);

        // Act
        $result = LogHelper::hashSensitiveData([
            'user_id' => 123,
            'email' => 'test@example.com',
        ]);

        // Assert
        $this->assertIsArray($result);
        $this->assertSame(123, $result['user_id']);
        $this->assertSame('test@example.com', $result['email']);
    }

    /**
     * 実際のSHA-256ハッシュ値を検証
     */
    public function test_hash_algorithm_is_sha256(): void
    {
        // Arrange
        config(['logging.hash_sensitive_data' => true]);

        // Act
        $hashedValue = LogHelper::hashSensitiveData(123);

        // Assert: 期待されるSHA-256ハッシュ値
        $expectedHash = hash('sha256', '123');
        $this->assertSame($expectedHash, $hashedValue);
    }
}
