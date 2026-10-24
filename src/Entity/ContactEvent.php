<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContactEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactEventRepository::class)]
#[ORM\Table(name: 'contact_events', indexes: [
    new ORM\Index(name: 'idx_contact_agency_date', columns: ['agency_id', 'occurred_at']),
    new ORM\Index(name: 'idx_contact_property_date', columns: ['property_id', 'occurred_at']),
    new ORM\Index(name: 'idx_contact_type_channel', columns: ['event_type', 'channel']),
])]
class ContactEvent
{
    public const TYPE_VIEW = 'view';
    public const TYPE_CONTACT = 'contact';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_EMAIL = 'email';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Agency::class)]
    #[ORM\JoinColumn(name: 'agency_id', nullable: false, onDelete: 'CASCADE')]
    private Agency $agency;

    #[ORM\ManyToOne(targetEntity: Property::class)]
    #[ORM\JoinColumn(name: 'property_id', nullable: false, onDelete: 'CASCADE')]
    private Property $property;

    #[ORM\Column(name: 'event_type', length: 20)]
    private string $eventType;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $channel = null;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    public function __construct(Agency $agency, Property $property, string $eventType, ?string $channel = null)
    {
        if (!in_array($eventType, [self::TYPE_VIEW, self::TYPE_CONTACT], true)) {
            throw new \InvalidArgumentException('Tipo de evento analítico inválido.');
        }

        if ($eventType === self::TYPE_VIEW && $channel !== null) {
            throw new \InvalidArgumentException('Visualizações não possuem canal de contato.');
        }

        if ($eventType === self::TYPE_CONTACT && !in_array($channel, [
            self::CHANNEL_WHATSAPP,
            self::CHANNEL_PHONE,
            self::CHANNEL_EMAIL,
        ], true)) {
            throw new \InvalidArgumentException('Canal de contato inválido.');
        }

        if ($property->getAgency() !== $agency) {
            throw new \InvalidArgumentException('O imóvel não pertence à imobiliária informada.');
        }

        $this->agency = $agency;
        $this->property = $property;
        $this->eventType = $eventType;
        $this->channel = $channel;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAgency(): Agency { return $this->agency; }
    public function getProperty(): Property { return $this->property; }
    public function getEventType(): string { return $this->eventType; }
    public function getChannel(): ?string { return $this->channel; }
    public function getOccurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
