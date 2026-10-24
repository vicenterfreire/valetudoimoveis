<?php
declare(strict_types=1);
namespace App\Repository;
use App\Entity\Agency; use App\Enum\AgencyStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository; use Doctrine\Persistence\ManagerRegistry;
/** @extends ServiceEntityRepository<Agency> */
class AgencyRepository extends ServiceEntityRepository {
 public function __construct(ManagerRegistry $r){parent::__construct($r,Agency::class);}
 public function findActiveByName(string $term=''):array{$q=$this->createQueryBuilder('a')->andWhere('a.status=:s')->setParameter('s',AgencyStatus::ACTIVE);if($term!=='')$q->andWhere('LOWER(a.name) LIKE :t')->setParameter('t','%'.mb_strtolower($term).'%');return $q->orderBy('a.name','ASC')->getQuery()->getResult();}
 public function findForAdmin(?string $term = null, ?AgencyStatus $status = null, int $page = 1, int $perPage = 25): array
 {
     $query = $this->createAdminQuery($term, $status)
         ->orderBy('a.createdAt', 'DESC')
         ->addOrderBy('a.id', 'DESC')
         ->setFirstResult((max(1, $page) - 1) * max(1, min(100, $perPage)))
         ->setMaxResults(max(1, min(100, $perPage)));

     return $query->getQuery()->getResult();
 }

 public function countForAdmin(?string $term = null, ?AgencyStatus $status = null): int
 {
     $query = $this->createAdminQuery($term, $status)->select('COUNT(a.id)');

     return (int) $query->getQuery()->getSingleScalarResult();
 }

 private function createAdminQuery(?string $term, ?AgencyStatus $status): \Doctrine\ORM\QueryBuilder
 {
     $query = $this->createQueryBuilder('a');
     if ($term !== null && trim($term) !== '') {
         $query->andWhere('(LOWER(a.name) LIKE :term OR LOWER(a.email) LIKE :term)')
             ->setParameter('term', '%' . mb_strtolower(trim($term)) . '%');
     }
     if ($status !== null) {
         $query->andWhere('a.status = :status')->setParameter('status', $status);
     }

     return $query;
 }

    public function findActiveBySlug(string $slug): ?Agency
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', AgencyStatus::ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $query->andWhere('a.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (int) $query->getQuery()->getSingleScalarResult() > 0;
    }
}
