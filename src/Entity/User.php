<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Agency::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'RESTRICT')]
    private ?Agency $agency = null;

    #[ORM\Column(length: 150)]
    private string $fullName = '';

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 255)]
    private string $password = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAgency(): ?Agency { return $this->agency; }
    public function setAgency(?Agency $value): self { $this->agency = $value; $this->touch(); return $this; }
    public function getFullName(): string { return $this->fullName; }
    public function setFullName(string $value): self { $this->fullName = trim($value); $this->touch(); return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $value): self { $this->email = mb_strtolower(trim($value)); $this->touch(); return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $value): self { $this->password = $value; $this->touch(); return $this; }
    public function setRoles(array $value): self { $this->roles = array_values(array_unique($value)); $this->touch(); return $this; }
    public function getRoles(): array { return array_values(array_unique([...$this->roles, 'ROLE_USER'])); }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $value): self { $this->isActive = $value; $this->touch(); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
    public function isGlobalAdmin(): bool { return in_array('ROLE_ADMIN', $this->getRoles(), true); }
    public function isAgencyManager(): bool { return in_array('ROLE_AGENCY_MANAGER', $this->getRoles(), true); }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
