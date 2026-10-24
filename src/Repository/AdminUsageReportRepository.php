<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/** Consultas agregadas dos relatórios globais; não carrega entidades em memória. */
final class AdminUsageReportRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return array<string, int> */
    public function overview(?int $agencyId = null): array
    {
        $agencyCounts = $this->connection->fetchAllKeyValue(
            'SELECT status, COUNT(id) FROM agencies GROUP BY status'
        );

        $agencyWhere = $agencyId !== null ? ' WHERE agency_id = :agencyId' : '';
        $parameters = $agencyId !== null ? ['agencyId' => $agencyId] : [];

        $userRows = $this->connection->fetchAssociative(
            'SELECT COUNT(id) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active, '
            . 'SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive FROM users'
            . ($agencyId !== null ? ' WHERE agency_id = :agencyId' : ''),
            $parameters
        ) ?: [];

        $propertyRows = $this->connection->fetchAllKeyValue(
            'SELECT status, COUNT(id) FROM properties' . $agencyWhere . ' GROUP BY status',
            $parameters
        );
        $moderationRows = $this->connection->fetchAllKeyValue(
            'SELECT moderation_status, COUNT(id) FROM properties' . $agencyWhere . ' GROUP BY moderation_status',
            $parameters
        );

        $result = [
            'agencies_active' => (int) ($agencyCounts['active'] ?? 0),
            'agencies_pending' => (int) ($agencyCounts['pending'] ?? 0),
            'agencies_blocked' => (int) ($agencyCounts['blocked'] ?? 0),
            'users_total' => (int) ($userRows['total'] ?? 0),
            'users_active' => (int) ($userRows['active'] ?? 0),
            'users_inactive' => (int) ($userRows['inactive'] ?? 0),
        ];

        foreach (['draft', 'published', 'paused', 'sold', 'rented', 'archived'] as $status) {
            $result['properties_' . $status] = (int) ($propertyRows[$status] ?? 0);
        }
        foreach (['pending', 'approved', 'rejected', 'changes_requested'] as $status) {
            $result['moderation_' . $status] = (int) ($moderationRows[$status] ?? 0);
        }

        return $result;
    }

    /** @return list<array{month:string,total:int}> */
    public function listingTrend(\DateTimeImmutable $from, \DateTimeImmutable $until, ?int $agencyId = null): array
    {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(id) AS total FROM properties "
            . 'WHERE created_at >= :from AND created_at < :until';
        $parameters = ['from' => $from, 'until' => $until];
        $types = ['from' => Types::DATETIME_IMMUTABLE, 'until' => Types::DATETIME_IMMUTABLE];
        if ($agencyId !== null) {
            $sql .= ' AND agency_id = :agencyId';
            $parameters['agencyId'] = $agencyId;
            $types['agencyId'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative($sql . ' GROUP BY month ORDER BY month ASC', $parameters, $types);
        return array_map(static fn (array $row): array => ['month' => (string) $row['month'], 'total' => (int) $row['total']], $rows);
    }

    /** @return list<array{month:string,views:int,contacts:int}> */
    public function contactTrend(\DateTimeImmutable $from, \DateTimeImmutable $until, ?int $agencyId = null): array
    {
        $sql = "SELECT DATE_FORMAT(occurred_at, '%Y-%m') AS month, "
            . "SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) AS views, "
            . "SUM(CASE WHEN event_type = 'contact' THEN 1 ELSE 0 END) AS contacts "
            . 'FROM contact_events WHERE occurred_at >= :from AND occurred_at < :until';
        $parameters = ['from' => $from, 'until' => $until];
        $types = ['from' => Types::DATETIME_IMMUTABLE, 'until' => Types::DATETIME_IMMUTABLE];
        if ($agencyId !== null) {
            $sql .= ' AND agency_id = :agencyId';
            $parameters['agencyId'] = $agencyId;
            $types['agencyId'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative($sql . ' GROUP BY month ORDER BY month ASC', $parameters, $types);
        return array_map(static fn (array $row): array => [
            'month' => (string) $row['month'],
            'views' => (int) $row['views'],
            'contacts' => (int) $row['contacts'],
        ], $rows);
    }

    /** @return list<array{id:int,name:string,status:string}> */
    public function agenciesForFilter(?int $selectedAgencyId = null): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, name, status FROM agencies ORDER BY name ASC LIMIT 500');
        $found = false;
        foreach ($rows as $row) {
            if ($selectedAgencyId !== null && (int) $row['id'] === $selectedAgencyId) {
                $found = true;
                break;
            }
        }
        if ($selectedAgencyId !== null && !$found) {
            $selected = $this->connection->fetchAssociative(
                'SELECT id, name, status FROM agencies WHERE id = :id',
                ['id' => $selectedAgencyId],
                ['id' => Types::INTEGER]
            );
            if ($selected !== false) {
                $rows[] = $selected;
            }
        }

        $agencies = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'status' => (string) $row['status'],
        ], $rows);
        usort($agencies, static fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));
        return $agencies;
    }
}
