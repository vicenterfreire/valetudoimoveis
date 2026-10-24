<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Property;
use App\Entity\PropertyHighlight;
use App\Enum\AgencyStatus;
use App\Enum\PropertyModerationStatus;
use App\Enum\PropertyStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PropertyHighlight> */
class PropertyHighlightRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyHighlight::class);
    }

    public function findCurrentForProperty(Property $property): ?PropertyHighlight
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.property = :property')
            ->andWhere('h.revokedAt IS NULL')
            ->andWhere('h.endsAt > :now')
            ->setParameter('property', $property)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('h.startsAt', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<PropertyHighlight> */
    public function findPublicActive(int $limit = 6): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('h')
            ->join('h.property', 'p')->addSelect('p')
            ->join('p.agency', 'a')->addSelect('a')
            ->andWhere('h.revokedAt IS NULL')
            ->andWhere('h.startsAt <= :now')
            ->andWhere('h.endsAt > :now')
            ->andWhere('p.status = :propertyStatus')
            ->andWhere('p.moderationStatus = :moderationStatus')
            ->andWhere('a.status = :agencyStatus')
            ->setParameter('now', $now)
            ->setParameter('propertyStatus', PropertyStatus::PUBLISHED)
            ->setParameter('moderationStatus', PropertyModerationStatus::APPROVED)
            ->setParameter('agencyStatus', AgencyStatus::ACTIVE)
            ->orderBy('h.startsAt', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setMaxResults(max(1, min(6, $limit)))
            ->getQuery()
            ->getResult();
    }

    public function revokeCurrentForProperty(Property $property, \App\Entity\User $actor, string $reason): bool
    {
        $current = $this->findCurrentForProperty($property);
        if ($current === null) {
            return false;
        }

        $current->revoke($actor, $reason);
        return true;
    }
}
