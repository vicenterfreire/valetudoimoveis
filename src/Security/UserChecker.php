<?php
declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        $this->assertAccountCanAuthenticate($user);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Revalida também usuários restaurados da sessão, pois a conta ou imobiliária
        // pode ter sido desativada/bloqueada desde o último login.
        $this->assertAccountCanAuthenticate($user);
    }

    private function assertAccountCanAuthenticate(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Esta conta está desativada. Entre em contato com o responsável pela imobiliária.');
        }

        if (!$user->isGlobalAdmin()) {
            $agency = $user->getAgency();
            if ($agency === null || !$agency->isActive()) {
                throw new CustomUserMessageAccountStatusException('O acesso está indisponível porque a imobiliária não está ativa.');
            }
        }
    }
}
