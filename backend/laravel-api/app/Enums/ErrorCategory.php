<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * エラーカテゴリーEnum
 *
 * @generated
 */
enum ErrorCategory: string
{
    case AUTH = 'AUTH';
    case VAL = 'VAL';
    case BIZ = 'BIZ';
    case INFRA = 'INFRA';
}
