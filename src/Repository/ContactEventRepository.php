<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Agency;
use App\Entity\ContactEvent;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ContactEvent> */
class ContactEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactEvent::class);
    }

    public function record(Agency $agency, Property $property, string $type, ?string $channel = null): void
    {
        $this->getEntityManager()->persist(new ContactEvent($agency, $property, $type, $channel));
        $this->getEntityManager()->flush();
    }


    public function deleteOlderThan(\DateTimeImmutable $cutoff): int
    {
        return $this->createQueryBuilder('e')
            ->delete()
            ->andWhere('e.occurredAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }

    /** @return array{views:int, contacts:int, whatsapp:int, phone:int, email:int} */
    public function summarizeForAgency(Agency $agency, \DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.eventType AS eventType, e.channel AS channel, COUNT(e.id) AS total')
            ->andWhere('e.agency = :agency')->setParameter('agency', $agency)
            ->andWhere('e.occurredAt >= :from')->setParameter('from', $from)
            ->andWhere('e.occurredAt < :until')->setParameter('until', $until)
            ->groupBy('e.eventType, e.channel')
            ->getQuery()->getArrayResult();

        $summary = ['views' => 0, 'contacts' => 0, 'whatsapp' => 0, 'phone' => 0, 'email' => 0];
        foreach ($rows as $row) {
            $count = (int) $row['total'];
            if ($row['eventType'] === ContactEvent::TYPE_VIEW) {
                $summary['views'] += $count;
                continue;
            }
            if ($row['eventType'] === ContactEvent::TYPE_CONTACT) {
                $summary['contacts'] += $count;
                if (isset($summary[$row['channel']])) {
                    $summary[$row['channel']] += $count;
                }
            }
        }

        return $summary;
    }

    /** @return list<array{propertyId:int, title:string, views:int, contacts:int}> */
    public function propertySummaryForAgency(Agency $agency, \DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        return $this->createQueryBuilder('e')
            ->select('p.id AS propertyId')
            ->addSelect('SUM(CASE WHEN e.eventType = :viewType THEN 1 ELSE 0 END) AS views')
            ->addSelect('SUM(CASE WHEN e.eventType = :contactType THEN 1 ELSE 0 END) AS contacts')
            ->addSelect('p.title AS title')
            ->join('e.property', 'p')
            ->andWhere('e.agency = :agency')->setParameter('agency', $agency)
            ->andWhere('e.occurredAt >= :from')->setParameter('from', $from)
            ->andWhere('e.occurredAt < :until')->setParameter('until', $until)
            ->setParameter('viewType', ContactEvent::TYPE_VIEW)
            ->setParameter('contactType', ContactEvent::TYPE_CONTACT)
            ->groupBy('p.id, p.title')
            ->orderBy('views', 'DESC')->addOrderBy('contacts', 'DESC')
            ->getQuery()->getArrayResult();
    }
}
