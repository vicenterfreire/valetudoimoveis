<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Agency;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/** @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException();
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    /** @return list<User> */
    public function findAgencyUsers(Agency $agency): array
    {
        return $this->createQueryBuilder('u')->andWhere('u.agency = :agency')->setParameter('agency', $agency)
            ->orderBy('u.fullName', 'ASC')->getQuery()->getResult();
    }

    /** @return list<User> */
    public function findForAdmin(?string $term, ?bool $active, ?string $role, ?int $agencyId, int $page = 1, int $perPage = 25): array
    {
        $perPage = max(1, min(100, $perPage));
        return $this->createAdminQuery($term, $active, $role, $agencyId)
            ->addSelect('a')
            ->orderBy('u.createdAt', 'DESC')
            ->addOrderBy('u.id', 'DESC')
            ->setFirstResult((max(1, $page) - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?string $term, ?bool $active, ?string $role, ?int $agencyId): int
    {
        return (int) $this->createAdminQuery($term, $active, $role, $agencyId)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createAdminQuery(?string $term, ?bool $active, ?string $role, ?int $agencyId): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->createQueryBuilder('u')->leftJoin('u.agency', 'a');
        if ($term !== null && trim($term) !== '') {
            $query->andWhere('(LOWER(u.fullName) LIKE :term OR LOWER(u.email) LIKE :term)')
                ->setParameter('term', '%' . mb_strtolower(trim($term)) . '%');
        }
        if ($active !== null) {
            $query->andWhere('u.isActive = :active')->setParameter('active', $active);
        }
        if ($agencyId !== null && $agencyId > 0) {
            $query->andWhere('a.id = :agencyId')->setParameter('agencyId', $agencyId);
        }
        if ($role !== null && in_array($role, ['ROLE_ADMIN', 'ROLE_AGENCY_MANAGER', 'ROLE_AGENCY_STAFF'], true)) {
            // roles is a JSON column. The quoted pattern matches one complete role token
            // and avoids loading an arbitrary number of users into PHP for filtering.
            $query->andWhere('u.roles LIKE :rolePattern')->setParameter('rolePattern', '%"' . $role . '"%');
        }

        return $query;
    }

    public function countOtherActiveGlobalAdmins(User $excluded): int
    {
        $admins = $this->findBy(['isActive' => true]);
        return count(array_filter($admins, static fn (User $user): bool => $user->getId() !== $excluded->getId() && $user->isGlobalAdmin()));
    }

    public function countActiveGlobalAdmins(): int
    {
        return count(array_filter($this->findBy(['isActive' => true]), static fn (User $user): bool => $user->isGlobalAdmin()));
    }
}
