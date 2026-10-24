<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Property;
use App\Entity\PropertyStatusHistory;
use App\Entity\User;
use App\Enum\PropertyModerationStatus;
use App\Enum\PropertyStatus;
use Doctrine\ORM\EntityManagerInterface;

final class PropertyStatusManager
{
    private const TRANSITIONS = [
        'draft' => ['published', 'archived'],
        'published' => ['paused', 'sold', 'rented', 'archived'],
        'paused' => ['published', 'sold', 'rented', 'archived'],
        'sold' => ['archived'],
        'rented' => ['archived'],
        'archived' => ['draft'],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function transition(Property $property, PropertyStatus $target, User $actor, ?string $reason = null): void
    {
        $current = $property->getStatus();
        if ($current === $target) {
            throw new \DomainException('O imóvel já está nesse status.');
        }

        if (!in_array($target->value, self::TRANSITIONS[$current->value] ?? [], true)) {
            throw new \DomainException(sprintf(
                'Não é permitido alterar o status de "%s" para "%s".',
                $current->label(),
                $target->label(),
            ));
        }

        $isManager = $actor->isAgencyManager() || $actor->isGlobalAdmin();
        if (($target === PropertyStatus::ARCHIVED || $current === PropertyStatus::ARCHIVED) && !$isManager) {
            throw new \DomainException('Somente um responsável pela imobiliária pode arquivar ou restaurar um imóvel.');
        }

        $reason = trim((string) $reason);
        if ($target === PropertyStatus::ARCHIVED && $reason === '') {
            throw new \DomainException('Informe o motivo do arquivamento.');
        }

        if ($target === PropertyStatus::PUBLISHED) {
            if ($property->getModerationStatus() !== PropertyModerationStatus::APPROVED) {
                throw new \DomainException('Este imóvel ainda não foi aprovado pela moderação.');
            }
            $this->assertReadyForPublication($property);
        }

        $property->setStatus($target)->touch();
        $this->entityManager->persist(new PropertyStatusHistory(
            $property,
            'commercial',
            $current->value,
            $target->value,
            $actor,
            $reason !== '' ? $reason : null,
        ));
    }

    public function assertReadyForPublication(Property $property): void
    {
        $errors = [];
        if ((float) $property->getPrice() <= 0) {
            $errors[] = 'o preço deve ser maior que zero';
        }
        if (mb_strlen(trim($property->getDescription())) < 30) {
            $errors[] = 'a descrição deve ter pelo menos 30 caracteres';
        }
        if (trim($property->getCity()) === '' || !preg_match('/^[A-Z]{2}$/', $property->getState())) {
            $errors[] = 'a cidade e a UF devem estar preenchidas corretamente';
        }
        if (count($property->getImages()) < 1) {
            $errors[] = 'adicione pelo menos uma foto';
        }

        if ($errors !== []) {
            throw new \DomainException('Não é possível publicar: ' . implode('; ', $errors) . '.');
        }
    }

    public function changeModerationStatus(
        Property $property,
        PropertyModerationStatus $target,
        User $actor,
        ?string $reason = null,
    ): void {
        $current = $property->getModerationStatus();
        if (in_array($target, [PropertyModerationStatus::REJECTED, PropertyModerationStatus::CHANGES_REQUESTED], true) && trim((string) $reason) === '') {
            throw new \DomainException('Informe o motivo da decisão de moderação.');
        }
        if ($reason !== null && mb_strlen(trim($reason)) > 2000) {
            throw new \DomainException('O motivo deve ter no máximo 2000 caracteres.');
        }
        if ($current === $target) {
            throw new \DomainException('O imóvel já está nesse status de moderação.');
        }

        $property->setModerationStatus($target)->touch();
        $this->entityManager->persist(new PropertyStatusHistory(
            $property,
            'moderation',
            $current->value,
            $target->value,
            $actor,
            $reason,
        ));
    }
}
