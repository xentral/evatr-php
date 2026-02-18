<?php

declare(strict_types=1);

namespace Xentral\EvatrPhp\Enum;

enum ComparisonResult: string
{
    case MATCH = 'A';
    case MISMATCH = 'B';
    case NOT_REQUESTED = 'C';
    case NOT_PROVIDED = 'D';
}
