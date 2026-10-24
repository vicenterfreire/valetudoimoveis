<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Property;
use App\Entity\PropertyStatusHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PropertyStatusHistory> */
class PropertyStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyStatusHistory::class);
    }

    /** @param list<Property> $properties @return array<int, PropertyStatusHistory> keyed by property ID */
    public function findLatestModerationForProperties(array $properties): array
    {
        $properties = array_values(array_filter($properties, static fn ($property): bool => $property instanceof Property));
        if ($properties === []) {
            return [];
        }
        $rows = $this->createQueryBuilder('h')
            ->leftJoin('h.actor', 'u')->addSelect('u')
            ->andWhere('h.property IN (:properties)')->setParameter('properties', $properties)
            ->andWhere('h.statusType = :type')->setParameter('type', 'moderation')
            ->orderBy('h.createdAt', 'DESC')->addOrderBy('h.id', 'DESC')
            ->getQuery()->getResult();
        $latest = [];
        foreach ($rows as $row) {
            if ($row instanceof PropertyStatusHistory && $row->getProperty()->getId() !== null && !isset($latest[$row->getProperty()->getId()])) {
                $latest[$row->getProperty()->getId()] = $row;
            }
        }
        return $latest;
    }

    /** @return list<PropertyStatusHistory> */
    public function findForProperty(Property $property): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.actor', 'u')
            ->addSelect('u')
            ->andWhere('h.property = :property')
            ->setParameter('property', $property)
            ->orderBy('h.createdAt', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}
