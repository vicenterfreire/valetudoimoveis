<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\UserAuditLogRepository::class)]
#[ORM\Table(name: 'user_audit_logs', indexes: [
    new ORM\Index(name: 'idx_user_audit_agency_date', columns: ['agency_id', 'created_at']),
    new ORM\Index(name: 'idx_user_audit_target_date', columns: ['target_user_id', 'created_at']),
    new ORM\Index(name: 'IDX_USER_AUDIT_ACTOR', columns: ['actor_user_id']),
])]
class UserAuditLog
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $actor;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'target_user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $targetUser;

    #[ORM\ManyToOne(targetEntity: Agency::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Agency $agency;

    #[ORM\Column(length: 40)]
    private string $action;

    #[ORM\Column(type: 'json')]
    private array $details;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(?User $actor, ?User $targetUser, ?Agency $agency, string $action, array $details = [])
    {
        $this->actor = $actor;
        $this->targetUser = $targetUser;
        $this->agency = $agency;
        $this->action = $action;
        $this->details = $details;
        $this->createdAt = new \DateTimeImmutable();
    }
}
