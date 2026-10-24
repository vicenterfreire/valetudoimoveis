<?php
declare(strict_types=1);

namespace App\Enum;

enum PropertyModerationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CHANGES_REQUESTED = 'changes_requested';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando moderação',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Reprovado',
            self::CHANGES_REQUESTED => 'Ajustes solicitados',
        };
    }
}
