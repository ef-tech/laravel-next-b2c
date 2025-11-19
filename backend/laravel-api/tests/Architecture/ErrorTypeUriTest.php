<?php

declare(strict_types=1);

use App\Enums\ErrorCode;

/**
 * RFC 7807 type URI統一検証: ErrorCode::getType()を唯一のtype URI生成元とする
 *
 * Requirements:
 * - 6.1: DomainExceptionのtoProblemDetails()メソッドがErrorCode::fromString()->getType()を使用すること
 * - 6.2: HasProblemDetails traitのtoProblemDetails()メソッドがErrorCode::fromString()->getType()を使用すること
 * - 6.3: ErrorCode enumの全ケースがtype URIを定義していること
 * - 6.4: type URIがhttps://で始まり/errors/を含むこと
 * - 6.5: config('app.url')による直接的な動的URI生成を禁止すること（フォールバック除く）
 */
// DomainExceptionはHasProblemDetailsトレイトを使用し、
// トレイトがErrorCodeを使用するため、間接的にErrorCodeを使用します
arch('DomainException must use HasProblemDetails trait which uses ErrorCode')
    ->expect('Ddd\Shared\Exceptions\DomainException')
    ->toUse([
        'Ddd\Shared\Exceptions\HasProblemDetails',
    ]);

arch('HasProblemDetails trait toProblemDetails() must use ErrorCode::fromString()->getType()')
    ->expect('Ddd\Shared\Exceptions\HasProblemDetails')
    ->toUse([
        'App\Enums\ErrorCode',
    ]);

// DomainExceptionはHasProblemDetailsトレイトからtoProblemDetails()を継承するため、
// トレイトのソースコードを検証します
test('DomainException uses HasProblemDetails trait for toProblemDetails()', function () {
    $reflection = new ReflectionClass(\Ddd\Shared\Exceptions\DomainException::class);
    $traits = $reflection->getTraitNames();

    // HasProblemDetailsトレイトを使用していることを検証
    expect($traits)->toContain('Ddd\Shared\Exceptions\HasProblemDetails');

    // toProblemDetails()メソッドが存在することを検証
    expect($reflection->hasMethod('toProblemDetails'))->toBeTrue();
});

test('HasProblemDetails trait source code contains ErrorCode::fromString()->getType()', function () {
    $reflection = new ReflectionClass(\Ddd\Shared\Exceptions\HasProblemDetails::class);
    $method = $reflection->getMethod('toProblemDetails');
    $fileName = $method->getFileName();

    expect($fileName)->not->toBeFalse();

    $content = file_get_contents($fileName);

    // ErrorCode::fromString()を使用していることを検証
    expect($content)->toContain('ErrorCode::fromString(')
        ->and($content)->toContain('?->getType()')
        ->and($content)->toContain('??'); // null coalescing operator for fallback
});

test('ErrorCode enum all cases must have type URIs', function () {
    $errorCodes = ErrorCode::cases();

    expect($errorCodes)->not->toBeEmpty();

    foreach ($errorCodes as $errorCode) {
        $typeUri = $errorCode->getType();

        // type URIが文字列型であることを検証
        expect($typeUri)->toBeString()
            ->and($typeUri)->not->toBeEmpty()
            // type URIがhttps://で始まることを検証
            ->and($typeUri)->toStartWith('https://')
            // type URIが/errors/を含むことを検証
            ->and($typeUri)->toContain('/errors/');
    }
});

test('ErrorCode::fromString() must return ErrorCode enum or null', function () {
    // 定義済みエラーコードでenum変換が成功することを検証
    $validCode = ErrorCode::fromString('AUTH-LOGIN-001');
    expect($validCode)->toBeInstanceOf(ErrorCode::class)
        ->and($validCode)->toBe(ErrorCode::AUTH_LOGIN_001);

    // 未定義エラーコードでnullが返されることを検証
    $invalidCode = ErrorCode::fromString('INVALID-CODE-9999');
    expect($invalidCode)->toBeNull();
});

test('ErrorCode::fromString() with valid code must return type URI from getType()', function () {
    $errorCode = ErrorCode::fromString('AUTH-LOGIN-001');

    expect($errorCode)->not->toBeNull();
    expect($errorCode->getType())->toBe('https://example.com/errors/auth/invalid-credentials');
});

test('Dynamic type URI generation using config("app.url") is prohibited in exception classes', function () {
    // DomainExceptionはHasProblemDetailsトレイトを使用するため、
    // トレイトのみを検証します
    $exceptionFiles = [
        base_path('ddd/Shared/Exceptions/HasProblemDetails.php'),
    ];

    foreach ($exceptionFiles as $file) {
        $content = file_get_contents($file);

        // ErrorCode::fromString()を使用していることを検証
        expect($content)->toContain('ErrorCode::fromString(');

        // フォールバックとしてのconfig('app.url')使用は許可（null coalescing operator右辺）
        // 直接的な動的URI生成（ErrorCode::fromString()なし）を禁止
        $lines = explode("\n", $content);
        foreach ($lines as $lineNumber => $line) {
            // config('app.url')を含む行を検出
            if (str_contains($line, "config('app.url')")) {
                // フォールバックとして使用されている場合のみ許可
                // null coalescing operator (??) の右辺にある場合は許可
                $previousLine = $lines[$lineNumber - 1] ?? '';
                $currentLine = $line;

                // ErrorCode::fromString()とセットで使われているかチェック
                expect($previousLine.$currentLine)->toContain('ErrorCode::fromString(');
            }
        }
    }
});

test('ApplicationException and InfrastructureException use HasProblemDetails trait', function () {
    $applicationException = new ReflectionClass(\Ddd\Shared\Exceptions\ApplicationException::class);
    $infrastructureException = new ReflectionClass(\Ddd\Shared\Exceptions\InfrastructureException::class);

    $applicationTraits = $applicationException->getTraitNames();
    $infrastructureTraits = $infrastructureException->getTraitNames();

    // HasProblemDetails traitを使用していることを検証
    expect($applicationTraits)->toContain('Ddd\Shared\Exceptions\HasProblemDetails')
        ->and($infrastructureTraits)->toContain('Ddd\Shared\Exceptions\HasProblemDetails');
});

test('All exception toProblemDetails() methods return type field with ErrorCode::getType() URI', function () {
    // テスト用例外クラス（ErrorCode enum定義済みエラーコード使用）
    $domainException = new class('Test exception') extends \Ddd\Shared\Exceptions\DomainException
    {
        public function getStatusCode(): int
        {
            return 401;
        }

        public function getErrorCode(): string
        {
            return 'AUTH-LOGIN-001'; // ErrorCode enumに定義済み
        }

        protected function getTitle(): string
        {
            return 'Test';
        }
    };

    $applicationException = new class('Test exception') extends \Ddd\Shared\Exceptions\ApplicationException
    {
        protected int $statusCode = 401;

        protected string $errorCode = 'AUTH-TOKEN-001'; // ErrorCode enumに定義済み

        protected function getTitle(): string
        {
            return 'Test';
        }
    };

    $infrastructureException = new class('Test exception') extends \Ddd\Shared\Exceptions\InfrastructureException
    {
        protected int $statusCode = 503;

        protected string $errorCode = 'INFRA-DB-001'; // ErrorCode enumに定義済み

        protected function getTitle(): string
        {
            return 'Test';
        }
    };

    // DomainExceptionのtype URIを検証
    $domainProblemDetails = $domainException->toProblemDetails();
    expect($domainProblemDetails['type'])->toBe('https://example.com/errors/auth/invalid-credentials');

    // ApplicationExceptionのtype URIを検証
    $applicationProblemDetails = $applicationException->toProblemDetails();
    expect($applicationProblemDetails['type'])->toBe('https://example.com/errors/auth/token-expired');

    // InfrastructureExceptionのtype URIを検証
    $infrastructureProblemDetails = $infrastructureException->toProblemDetails();
    expect($infrastructureProblemDetails['type'])->toBe('https://example.com/errors/infrastructure/database-unavailable');
});

test('Fallback type URI generation works for undefined error codes', function () {
    // ErrorCode enumに未定義のエラーコード
    $customException = new class('Custom exception') extends \Ddd\Shared\Exceptions\DomainException
    {
        public function getStatusCode(): int
        {
            return 400;
        }

        public function getErrorCode(): string
        {
            return 'CUSTOM-UNDEFINED-9999'; // ErrorCode enumに未定義
        }

        protected function getTitle(): string
        {
            return 'Custom Error';
        }
    };

    $problemDetails = $customException->toProblemDetails();

    // フォールバックURIが生成されることを検証
    expect($problemDetails['type'])
        ->toContain(config('app.url'))
        ->toContain('/errors/')
        ->toContain('custom-undefined-9999'); // 小文字変換

    // ErrorCode::fromString()でnullが返されることを検証
    $errorCode = ErrorCode::fromString('CUSTOM-UNDEFINED-9999');
    expect($errorCode)->toBeNull();
});
