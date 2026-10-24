<?php
declare(strict_types=1);

namespace App\Enum;

enum AgencyStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case REJECTED = 'rejected';
}
