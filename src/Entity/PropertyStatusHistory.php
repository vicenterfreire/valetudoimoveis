<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\PropertyStatusHistoryRepository;
use App\Enum\PropertyStatus;
use App\Enum\PropertyModerationStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyStatusHistoryRepository::class)]
#[ORM\Table(name: 'property_status_history', indexes: [
    new ORM\Index(name: 'idx_property_history_property_date', columns: ['property_id', 'created_at']),
    new ORM\Index(name: 'idx_property_history_actor', columns: ['actor_user_id']),
])]
class PropertyStatusHistory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(length: 20)]
    private string $statusType = 'commercial';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $previousStatus = null;

    #[ORM\Column(length: 30)]
    private string $newStatus = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Property $property,
        string $statusType,
        ?string $previousStatus,
        string $newStatus,
        ?User $actor = null,
        ?string $reason = null,
    ) {
        if (!in_array($statusType, ['commercial', 'moderation'], true)) {
            throw new \InvalidArgumentException('Tipo de histórico inválido.');
        }

        $this->property = $property;
        $this->statusType = $statusType;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->actor = $actor;
        $this->reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProperty(): Property { return $this->property; }
    public function getActor(): ?User { return $this->actor; }
    public function getStatusType(): string { return $this->statusType; }
    public function getPreviousStatus(): ?string { return $this->previousStatus; }
    public function getNewStatus(): string { return $this->newStatus; }
    public function getReason(): ?string { return $this->reason; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getStatusTypeLabel(): string
    {
        return $this->statusType === 'moderation' ? 'Moderação' : 'Status comercial';
    }

    public function getPreviousStatusLabel(): ?string
    {
        if ($this->previousStatus === null) {
            return null;
        }

        if ($this->statusType === 'moderation') {
            return PropertyModerationStatus::tryFrom($this->previousStatus)?->label() ?? 'Status anterior';
        }

        return PropertyStatus::tryFrom($this->previousStatus)?->label() ?? 'Status anterior';
    }

    public function getNewStatusLabel(): string
    {
        if ($this->statusType === 'moderation') {
            return PropertyModerationStatus::tryFrom($this->newStatus)?->label() ?? 'Status desconhecido';
        }

        return PropertyStatus::tryFrom($this->newStatus)?->label() ?? 'Status desconhecido';
    }
}
