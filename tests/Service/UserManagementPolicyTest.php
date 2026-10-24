<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserManagementPolicy;
use PHPUnit\Framework\TestCase;

final class UserManagementPolicyTest extends TestCase
{
    public function testAdministratorCannotManageTheirOwnAccount(): void
    {
        $admin = $this->user(10, ['ROLE_ADMIN']);
        $repository = $this->createMock(UserRepository::class);
        $policy = new UserManagementPolicy($repository);

        self::assertFalse($policy->canManageTarget($admin, $admin));
    }

    public function testAdministratorCanManageAnotherAccount(): void
    {
        $admin = $this->user(10, ['ROLE_ADMIN']);
        $target = $this->user(20, ['ROLE_AGENCY_STAFF']);
        $repository = $this->createMock(UserRepository::class);
        $policy = new UserManagementPolicy($repository);

        self::assertTrue($policy->canManageTarget($admin, $target));
    }

    public function testCannotRemoveRoleFromLastActiveAdministrator(): void
    {
        $admin = $this->user(10, ['ROLE_ADMIN']);
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())
            ->method('countOtherActiveGlobalAdmins')
            ->with($admin)
            ->willReturn(0);

        self::assertTrue((new UserManagementPolicy($repository))->wouldRemoveLastActiveAdmin($admin, 'ROLE_AGENCY_MANAGER'));
    }

    public function testCanChangeAdministratorRoleWhenAnotherActiveAdministratorExists(): void
    {
        $admin = $this->user(10, ['ROLE_ADMIN']);
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())
            ->method('countOtherActiveGlobalAdmins')
            ->with($admin)
            ->willReturn(1);

        self::assertFalse((new UserManagementPolicy($repository))->wouldRemoveLastActiveAdmin($admin, 'ROLE_AGENCY_MANAGER'));
    }

    public function testCanDeactivateAccountThatIsNotAnActiveAdministrator(): void
    {
        $staff = $this->user(20, ['ROLE_AGENCY_STAFF']);
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('countOtherActiveGlobalAdmins');

        self::assertFalse((new UserManagementPolicy($repository))->wouldRemoveLastActiveAdmin($staff, null, false));
    }

    private function user(int $id, array $roles): User
    {
        $user = (new User())
            ->setEmail(sprintf('user%d@example.test', $id))
            ->setRoles($roles);

        $idProperty = new \ReflectionProperty(User::class, 'id');
        $idProperty->setValue($user, $id);

        return $user;
    }
}
