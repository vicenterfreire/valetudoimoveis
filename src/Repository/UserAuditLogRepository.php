<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UserAuditLog> */
final class UserAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAuditLog::class);
    }

    public function deleteOlderThan(\DateTimeImmutable $cutoff): int
    {
        return $this->createQueryBuilder('l')->delete()->andWhere('l.createdAt < :cutoff')->setParameter('cutoff', $cutoff)->getQuery()->execute();
    }
}
