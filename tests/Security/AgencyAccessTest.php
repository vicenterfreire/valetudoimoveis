<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Agency;
use App\Entity\Property;
use App\Entity\User;
use App\Security\AgencyAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AgencyAccessTest extends TestCase
{
    public function testStaffCannotUseManagerOnlyOperation(): void
    {
        $agency = $this->activeAgency('agency-one');
        $staff = (new User())->setAgency($agency)->setRoles(['ROLE_AGENCY_STAFF']);

        $this->expectException(AccessDeniedException::class);
        (new AgencyAccess())->requireManager($staff);
    }

    public function testManagerCanManageTheirOwnAgency(): void
    {
        $agency = $this->activeAgency('agency-one');
        $manager = (new User())->setAgency($agency)->setRoles(['ROLE_AGENCY_MANAGER']);

        self::assertSame($agency, (new AgencyAccess())->requireManager($manager));
    }

    public function testUserCannotManagePropertyOwnedByAnotherAgency(): void
    {
        $ownAgency = $this->activeAgency('agency-one', 101);
        $otherAgency = $this->activeAgency('agency-two', 202);
        $user = (new User())->setAgency($ownAgency)->setRoles(['ROLE_AGENCY_STAFF']);
        $property = (new Property())->setAgency($otherAgency);

        $this->expectException(AccessDeniedException::class);
        (new AgencyAccess())->assertCanManageProperty($user, $property);
    }

    public function testUserCanManagePropertyOwnedByTheirAgency(): void
    {
        $agency = $this->activeAgency('agency-one', 101);
        $user = (new User())->setAgency($agency)->setRoles(['ROLE_AGENCY_STAFF']);
        $property = (new Property())->setAgency($agency);

        (new AgencyAccess())->assertCanManageProperty($user, $property);
        self::assertSame($agency, $property->getAgency());
    }

    private function activeAgency(string $slug, ?int $id = null): Agency
    {
        $agency = (new Agency())->setName('Teste '.$slug)->setSlug($slug);
        $agency->approve();

        if ($id !== null) {
            $idProperty = new \ReflectionProperty(Agency::class, 'id');
            $idProperty->setValue($agency, $id);
        }

        return $agency;
    }
}
