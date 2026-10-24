<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Agency;
use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    public function testInactiveAccountCannotAuthenticate(): void
    {
        $user = (new User())->setEmail('inactive@example.test')->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        (new UserChecker())->checkPreAuth($user);
    }

    public function testUserWithoutAgencyCannotAuthenticate(): void
    {
        $user = (new User())->setEmail('orphan@example.test')->setRoles(['ROLE_AGENCY_STAFF']);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        (new UserChecker())->checkPreAuth($user);
    }

    public function testUserFromBlockedAgencyCannotAuthenticate(): void
    {
        $agency = (new Agency())->setName('Imobiliária de teste')->setSlug('imobiliaria-teste');
        $agency->approve();
        $agency->block('Teste de bloqueio');
        $user = (new User())->setEmail('staff@example.test')->setAgency($agency);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        (new UserChecker())->checkPostAuth($user);
    }

    public function testActiveUserFromActiveAgencyCanAuthenticate(): void
    {
        $agency = (new Agency())->setName('Imobiliária ativa')->setSlug('imobiliaria-ativa');
        $agency->approve();
        $user = (new User())->setEmail('staff@example.test')->setAgency($agency);

        $checker = new UserChecker();
        $checker->checkPreAuth($user);
        $checker->checkPostAuth($user);

        self::assertTrue($user->isActive());
        self::assertTrue($agency->isActive());
    }

    public function testGlobalAdministratorDoesNotRequireAnAgency(): void
    {
        $admin = (new User())->setEmail('admin@example.test')->setRoles(['ROLE_ADMIN']);

        $checker = new UserChecker();
        $checker->checkPreAuth($admin);
        $checker->checkPostAuth($admin);

        self::assertTrue($admin->isGlobalAdmin());
    }
}
