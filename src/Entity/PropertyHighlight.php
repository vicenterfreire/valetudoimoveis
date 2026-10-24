<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\PropertyHighlightRepository::class)]
#[ORM\Table(name: 'property_highlights', indexes: [
    new ORM\Index(name: 'idx_highlight_property_active', columns: ['property_id', 'revoked_at', 'ends_at']),
    new ORM\Index(name: 'idx_highlight_public_order', columns: ['starts_at', 'ends_at', 'revoked_at']),
])]
class PropertyHighlight
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endsAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $grantedBy;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $revokedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $revocationReason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Property $property,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        User $grantedBy,
        string $reason,
    ) {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 1000) {
            throw new \InvalidArgumentException('O motivo do destaque deve conter entre 1 e 1000 caracteres.');
        }
        if ($endsAt <= $startsAt) {
            throw new \InvalidArgumentException('A data final deve ser posterior à data inicial.');
        }

        $this->property = $property;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->grantedBy = $grantedBy;
        $this->reason = $reason;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProperty(): Property { return $this->property; }
    public function getStartsAt(): \DateTimeImmutable { return $this->startsAt; }
    public function getEndsAt(): \DateTimeImmutable { return $this->endsAt; }
    public function getGrantedBy(): ?User { return $this->grantedBy; }
    public function getReason(): string { return $this->reason; }
    public function getRevokedAt(): ?\DateTimeImmutable { return $this->revokedAt; }
    public function getRevokedBy(): ?User { return $this->revokedBy; }
    public function getRevocationReason(): ?string { return $this->revocationReason; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isInWindow(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable();
        return $this->revokedAt === null && $this->startsAt <= $now && $this->endsAt > $now;
    }

    public function revoke(User $actor, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 1000) {
            throw new \InvalidArgumentException('Informe um motivo para remover o destaque, com até 1000 caracteres.');
        }
        if ($this->revokedAt !== null) {
            throw new \DomainException('Este destaque já foi removido.');
        }

        $this->revokedAt = new \DateTimeImmutable();
        $this->revokedBy = $actor;
        $this->revocationReason = $reason;
    }
}
