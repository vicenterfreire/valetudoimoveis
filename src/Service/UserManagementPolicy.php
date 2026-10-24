<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

final class UserManagementPolicy
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function canManageTarget(User $actor, User $target): bool
    {
        return $actor->isGlobalAdmin() && $actor->getId() !== $target->getId();
    }

    public function wouldRemoveLastActiveAdmin(User $target, ?string $newRole = null, ?bool $newActive = null): bool
    {
        if (!$target->isGlobalAdmin() || !$target->isActive()) {
            return false;
        }
        $removesAdminAccess = ($newRole !== null && $newRole !== 'ROLE_ADMIN') || $newActive === false;
        return $removesAdminAccess && $this->users->countOtherActiveGlobalAdmins($target) < 1;
    }
}
