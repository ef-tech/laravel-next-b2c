<?php

declare(strict_types=1);

use Ddd\Domain\Admin\ValueObjects\AdminId;

test('AdminId can be created with valid string', function () {
    $id = new AdminId('123');

    expect($id->value)->toBe('123');
});

test('AdminId equals returns true for same value', function () {
    $id1 = new AdminId('123');
    $id2 = new AdminId('123');

    expect($id1->equals($id2))->toBeTrue();
});

test('AdminId equals returns false for different value', function () {
    $id1 = new AdminId('123');
    $id2 = new AdminId('456');

    expect($id1->equals($id2))->toBeFalse();
});
