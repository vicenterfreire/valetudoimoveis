<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Agency;
use App\Entity\User;
use App\Entity\UserAuditLog;
use Doctrine\ORM\EntityManagerInterface;

final class UserAuditLogger
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function record(?User $actor, ?User $target, ?Agency $agency, string $action, array $details = []): void
    {
        if ($action === '' || mb_strlen($action) > 40 || !preg_match('/^[a-z0-9_]+$/', $action)) {
            throw new \InvalidArgumentException('Identificador de ação de auditoria inválido.');
        }

        // Only allowlisted, non-secret metadata should be passed by callers; never log raw form data.
        $allowed = ['changed_fields', 'old_status', 'new_status', 'token_type', 'old_roles', 'new_roles', 'old_agency_id', 'new_agency_id', 'property_id', 'image_count', 'file_kind', 'reason_code'];
        $safeDetails = array_intersect_key($details, array_flip($allowed));
        foreach ($safeDetails as $key => $value) {
            if (is_string($value)) {
                $safeDetails[$key] = mb_substr($value, 0, 80);
            } elseif (is_array($value)) {
                $safeDetails[$key] = array_slice(array_values(array_filter($value, static fn ($item): bool => is_string($item) || is_int($item))), 0, 20);
            } elseif (!is_int($value) && $value !== null && !is_bool($value)) {
                unset($safeDetails[$key]);
            }
        }
        $this->entityManager->persist(new UserAuditLog($actor, $target, $agency, $action, $safeDetails));
    }
}
