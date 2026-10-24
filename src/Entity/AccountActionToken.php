<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'account_action_tokens', indexes: [new ORM\Index(name: 'idx_account_action_user_type', columns: ['user_id', 'type'])])]
class AccountActionToken
{
    public const EMAIL_CHANGE = 'email_change';
    public const PASSWORD_RESET = 'password_reset';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 32)]
    private string $type;

    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $requestedEmail;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, string $type, string $tokenHash, ?string $requestedEmail, \DateTimeImmutable $expiresAt)
    {
        if (!in_array($type, [self::EMAIL_CHANGE, self::PASSWORD_RESET], true)) {
            throw new \InvalidArgumentException('Tipo de token de conta inválido.');
        }

        $this->user = $user;
        $this->type = $type;
        $this->tokenHash = $tokenHash;
        $this->requestedEmail = $requestedEmail;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getType(): string { return $this->type; }
    public function getRequestedEmail(): ?string { return $this->requestedEmail; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function isUsable(\DateTimeImmutable $now, string $type): bool
    {
        return $this->type === $type && $this->usedAt === null && $this->expiresAt > $now;
    }
    public function markUsed(): void { $this->usedAt = new \DateTimeImmutable(); }
}
