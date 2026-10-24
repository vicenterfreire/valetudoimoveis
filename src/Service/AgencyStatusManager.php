<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Agency;
use App\Entity\AgencyStatusHistory;
use App\Entity\User;
use App\Enum\AgencyStatus;
use Doctrine\ORM\EntityManagerInterface;

final class AgencyStatusManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function approve(Agency $agency, User $admin, ?string $reason = null): void
    {
        $previous = $agency->getStatus()->value;
        $agency->approve();
        $this->em->persist(new AgencyStatusHistory($agency, $admin, $previous, AgencyStatus::ACTIVE->value, $reason));
        $this->em->flush();
    }

    public function reject(Agency $agency, User $admin, string $reason): void
    {
        $previous = $agency->getStatus()->value;
        $agency->reject($reason);
        $this->em->persist(new AgencyStatusHistory($agency, $admin, $previous, AgencyStatus::REJECTED->value, trim($reason)));
        $this->em->flush();
    }

    public function block(Agency $agency, User $admin, ?string $reason): void
    {
        if ($agency->getStatus() !== AgencyStatus::ACTIVE) {
            throw new \DomainException('Somente imobiliárias ativas podem ser bloqueadas.');
        }
        $previous = $agency->getStatus()->value;
        $agency->block($reason);
        $this->em->persist(new AgencyStatusHistory($agency, $admin, $previous, AgencyStatus::BLOCKED->value, $reason));
        $this->em->flush();
    }

    public function activate(Agency $agency, User $admin): void
    {
        $previous = $agency->getStatus()->value;
        $agency->activate();
        $this->em->persist(new AgencyStatusHistory($agency, $admin, $previous, AgencyStatus::ACTIVE->value));
        $this->em->flush();
    }
}
