<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Agency;
use App\Entity\Property;
use App\Enum\AgencyStatus;
use App\Enum\ListingType;
use App\Enum\PropertyStatus;
use App\Enum\PropertyModerationStatus;
use App\Enum\PropertyType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Property> */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function searchPublic(array $filters = [], int $limit = 24, int $offset = 0, string $sort = 'recent'): array
    {
        $query = $this->createPublicSearchQueryBuilder($filters);
        $this->applyPublicSort($query, $sort);

        return $query
            ->setMaxResults(max(1, min(100, $limit)))
            ->setFirstResult(max(0, $offset))
            ->getQuery()
            ->getResult();
    }

    public function countPublic(array $filters = []): int
    {
        $query = $this->createPublicSearchQueryBuilder($filters);
        $query->select('COUNT(DISTINCT p.id)')->resetDQLPart('orderBy');

        return (int) $query->getQuery()->getSingleScalarResult();
    }

    /** @return list<Property> */
    public function findAllPublicForSitemap(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.agency', 'a')
            ->andWhere('p.status = :propertyStatus')
            ->andWhere('p.moderationStatus = :moderationStatus')
            ->andWhere('a.status = :agencyStatus')
            ->setParameter('propertyStatus', PropertyStatus::PUBLISHED)
            ->setParameter('moderationStatus', PropertyModerationStatus::APPROVED)
            ->setParameter('agencyStatus', AgencyStatus::ACTIVE)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function createPublicSearchQueryBuilder(array $filters): QueryBuilder
    {
        $query = $this->createQueryBuilder('p')
            ->join('p.agency', 'a')
            ->addSelect('a')
            ->andWhere('p.status = :propertyStatus')
            ->setParameter('propertyStatus', PropertyStatus::PUBLISHED)
            ->andWhere('p.moderationStatus = :moderationStatus')
            ->setParameter('moderationStatus', PropertyModerationStatus::APPROVED)
            ->andWhere('a.status = :agencyStatus')
            ->setParameter('agencyStatus', AgencyStatus::ACTIVE);

        if (($filters['city'] ?? '') !== '') {
            $query->andWhere('LOWER(p.city) LIKE :city')
                ->setParameter('city', '%' . mb_strtolower(trim((string) $filters['city'])) . '%');
        }

        if (($filters['neighborhood'] ?? '') !== '') {
            $query->andWhere('LOWER(p.neighborhood) LIKE :neighborhood')
                ->setParameter('neighborhood', '%' . mb_strtolower(trim((string) $filters['neighborhood'])) . '%');
        }

        if (($filters['listingType'] ?? '') !== '' && ($listingType = ListingType::tryFrom((string) $filters['listingType'])) !== null) {
            $query->andWhere('p.listingType = :listingType')->setParameter('listingType', $listingType);
        }

        if (($filters['propertyType'] ?? '') !== '' && ($propertyType = PropertyType::tryFrom((string) $filters['propertyType'])) !== null) {
            $query->andWhere('p.propertyType = :propertyType')->setParameter('propertyType', $propertyType);
        }

        $minimum = $this->normalizePrice($filters['minPrice'] ?? null);
        $maximum = $this->normalizePrice($filters['maxPrice'] ?? null);

        if ($minimum !== null) {
            $query->andWhere('p.price >= :minimumPrice')->setParameter('minimumPrice', $minimum);
        }
        if ($maximum !== null) {
            $query->andWhere('p.price <= :maximumPrice')->setParameter('maximumPrice', $maximum);
        }

        foreach ([
            'bedrooms' => 'p.bedrooms',
            'bathrooms' => 'p.bathrooms',
            'parkingSpaces' => 'p.parkingSpaces',
        ] as $filter => $field) {
            $value = $filters[$filter] ?? '';
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value >= 0 && (int) $value <= 20) {
                $query->andWhere($field . ' >= :' . $filter)->setParameter($filter, (int) $value);
            }
        }

        $minimumArea = $this->normalizeArea($filters['minArea'] ?? null);
        $maximumArea = $this->normalizeArea($filters['maxArea'] ?? null);
        if ($minimumArea !== null) {
            $query->andWhere('p.areaM2 >= :minimumArea')->setParameter('minimumArea', $minimumArea);
        }
        if ($maximumArea !== null) {
            $query->andWhere('p.areaM2 <= :maximumArea')->setParameter('maximumArea', $maximumArea);
        }

        return $query;
    }

    private function normalizeArea(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $area = (float) $value;
        return is_finite($area) && $area >= 0 && $area <= 1000000 ? $area : null;
    }

    private function normalizePrice(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $price = (float) $value;
        return is_finite($price) && $price >= 0 ? $price : null;
    }

    private function applyPublicSort(QueryBuilder $query, string $sort): void
    {
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('p.price', 'ASC')->addOrderBy('p.publishedAt', 'DESC')->addOrderBy('p.id', 'DESC');
                break;
            case 'price_desc':
                $query->orderBy('p.price', 'DESC')->addOrderBy('p.publishedAt', 'DESC')->addOrderBy('p.id', 'DESC');
                break;
            case 'recent':
            default:
                $query->orderBy('p.publishedAt', 'DESC')->addOrderBy('p.id', 'DESC');
                break;
        }
    }


    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $query->andWhere('p.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (int) $query->getQuery()->getSingleScalarResult() > 0;
    }

    public function findForAgency(Agency $agency, ?string $term = null): array
    {
        $query = $this->createQueryBuilder('p')->andWhere('p.agency = :agency')->setParameter('agency', $agency);
        if ($term) {
            $query->andWhere('LOWER(p.title) LIKE :term')->setParameter('term', '%' . mb_strtolower($term) . '%');
        }

        return $query->orderBy('p.updatedAt', 'DESC')->getQuery()->getResult();
    }

    /** @return list<Property> */
    public function findForModeration(
        ?string $term = null,
        ?int $agencyId = null,
        ?PropertyStatus $commercialStatus = null,
        ?PropertyModerationStatus $moderationStatus = null,
        ?string $city = null,
        ?PropertyType $type = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $toExclusive = null,
        int $page = 1,
        int $perPage = 25,
    ): array {
        $perPage = max(1, min(100, $perPage));

        return $this->createModerationQuery($term, $agencyId, $commercialStatus, $moderationStatus, $city, $type, $from, $toExclusive)
            ->orderBy('p.updatedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setFirstResult((max(1, $page) - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countForModeration(
        ?string $term = null,
        ?int $agencyId = null,
        ?PropertyStatus $commercialStatus = null,
        ?PropertyModerationStatus $moderationStatus = null,
        ?string $city = null,
        ?PropertyType $type = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $toExclusive = null,
    ): int {
        return (int) $this->createModerationQuery($term, $agencyId, $commercialStatus, $moderationStatus, $city, $type, $from, $toExclusive)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createModerationQuery(
        ?string $term,
        ?int $agencyId,
        ?PropertyStatus $commercialStatus,
        ?PropertyModerationStatus $moderationStatus,
        ?string $city,
        ?PropertyType $type,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $toExclusive,
    ): QueryBuilder {
        // The list does not render images; avoiding the collection join prevents duplicate SQL rows
        // from consuming pagination slots when a property has multiple photos.
        $query = $this->createQueryBuilder('p')->join('p.agency', 'a')->addSelect('a');

        if ($term !== null && trim($term) !== '') {
            $query->andWhere('(LOWER(p.title) LIKE :term OR LOWER(a.name) LIKE :term)')
                ->setParameter('term', '%' . mb_strtolower(trim($term)) . '%');
        }
        if ($agencyId !== null && $agencyId > 0) {
            $query->andWhere('a.id = :agencyId')->setParameter('agencyId', $agencyId);
        }
        if ($commercialStatus !== null) {
            $query->andWhere('p.status = :commercialStatus')->setParameter('commercialStatus', $commercialStatus);
        }
        if ($moderationStatus !== null) {
            $query->andWhere('p.moderationStatus = :moderationStatus')->setParameter('moderationStatus', $moderationStatus);
        }
        if ($city !== null && trim($city) !== '') {
            $query->andWhere('LOWER(p.city) LIKE :city')->setParameter('city', '%' . mb_strtolower(trim($city)) . '%');
        }
        if ($type !== null) {
            $query->andWhere('p.propertyType = :type')->setParameter('type', $type);
        }
        if ($from !== null) {
            $query->andWhere('p.createdAt >= :fromDate')->setParameter('fromDate', $from);
        }
        if ($toExclusive !== null) {
            $query->andWhere('p.createdAt < :toDate')->setParameter('toDate', $toExclusive);
        }

        return $query;
    }

    public function findPublicById(int $id): ?Property
    {
        return $this->createQueryBuilder('p')
            ->join('p.agency', 'a')->addSelect('a')
            ->leftJoin('p.images', 'i')->addSelect('i')
            ->andWhere('p.id = :id')->setParameter('id', $id)
            ->andWhere('p.status = :propertyStatus')->setParameter('propertyStatus', PropertyStatus::PUBLISHED)
            ->andWhere('p.moderationStatus = :moderationStatus')->setParameter('moderationStatus', PropertyModerationStatus::APPROVED)
            ->andWhere('a.status = :agencyStatus')->setParameter('agencyStatus', AgencyStatus::ACTIVE)
            ->orderBy('i.sortOrder', 'ASC')->addOrderBy('i.id', 'ASC')
            ->getQuery()->getOneOrNullResult();
    }

    /** @return list<Property> */
    public function findSimilarPublic(Property $property, int $limit = 3): array
    {
        $query = $this->createQueryBuilder('p')
            ->join('p.agency', 'a')->addSelect('a')
            ->andWhere('p.status = :propertyStatus')
            ->andWhere('p.moderationStatus = :moderationStatus')
            ->andWhere('a.status = :agencyStatus')
            ->andWhere('p.id != :currentId')
            ->setParameter('propertyStatus', PropertyStatus::PUBLISHED)
            ->setParameter('moderationStatus', PropertyModerationStatus::APPROVED)
            ->setParameter('agencyStatus', AgencyStatus::ACTIVE)
            ->setParameter('currentId', $property->getId())
            ->addSelect("CASE WHEN p.neighborhood = :neighborhood THEN 0 ELSE 1 END AS HIDDEN neighborhoodRank")
            ->addSelect("CASE WHEN p.propertyType = :propertyType THEN 0 ELSE 1 END AS HIDDEN typeRank")
            ->setParameter('neighborhood', $property->getNeighborhood())
            ->setParameter('propertyType', $property->getPropertyType())
            ->andWhere('p.city = :city')
            ->setParameter('city', $property->getCity())
            ->orderBy('neighborhoodRank', 'ASC')
            ->addOrderBy('typeRank', 'ASC')
            ->addOrderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults(max(1, min(6, $limit)));

        $results = $query->getQuery()->getResult();
        $properties = [];
        foreach ($results as $result) {
            if ($result instanceof Property && !isset($properties[$result->getId()])) {
                $properties[$result->getId()] = $result;
            }
        }

        return array_slice(array_values($properties), 0, max(1, min(6, $limit)));
    }


    /** @return list<Property> */
    public function findPublicByAgency(Agency $agency, int $limit = 24): array
    {
        $limit = max(1, min(48, $limit));
        $ids = $this->createQueryBuilder('p')
            ->select('p.id')
            ->andWhere('p.agency = :agency')
            ->andWhere('p.status = :propertyStatus')
            ->andWhere('p.moderationStatus = :moderationStatus')
            ->setParameter('agency', $agency)
            ->setParameter('propertyStatus', PropertyStatus::PUBLISHED)
            ->setParameter('moderationStatus', PropertyModerationStatus::APPROVED)
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')->addSelect('i')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('i.sortOrder', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
