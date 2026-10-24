<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Property;
use App\Entity\PropertyHighlight;
use App\Entity\User;
use App\Enum\PropertyModerationStatus;
use App\Enum\PropertyStatus;
use App\Enum\PropertyType;
use App\Repository\AgencyRepository;
use App\Repository\PropertyRepository;
use App\Repository\PropertyHighlightRepository;
use App\Repository\PropertyStatusHistoryRepository;
use App\Service\PropertyStatusManager;
use App\Service\UserAuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/imoveis', name: 'admin_properties_')]
#[IsGranted('ROLE_ADMIN')]
final class PropertyAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PropertyRepository $properties, AgencyRepository $agencies): Response
    {
        $term = $request->query->get('q', '');
        $term = is_string($term) ? mb_substr(trim($term), 0, 100) : '';
        $city = $request->query->get('city', '');
        $city = is_string($city) ? mb_substr(trim($city), 0, 100) : '';
        $agencyRaw = $request->query->get('agency', '');
        $agencyId = is_string($agencyRaw) && ctype_digit($agencyRaw) && (int) $agencyRaw > 0 ? (int) $agencyRaw : null;
        $commercialRaw = $request->query->get('commercial', '');
        $commercial = is_string($commercialRaw) ? PropertyStatus::tryFrom($commercialRaw) : null;
        $moderationRaw = $request->query->get('moderation', '');
        $moderation = is_string($moderationRaw) ? PropertyModerationStatus::tryFrom($moderationRaw) : null;
        $typeRaw = $request->query->get('type', '');
        $type = is_string($typeRaw) ? PropertyType::tryFrom($typeRaw) : null;
        $from = $this->parseDate($request->query->get('from', ''), false);
        $to = $this->parseDate($request->query->get('to', ''), true);

        $perPage = 25;
        $totalItems = $properties->countForModeration($term, $agencyId, $commercial, $moderation, $city, $type, $from, $to);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $rawPage = $request->query->get('page', '1');
        $page = is_scalar($rawPage) && ctype_digit((string) $rawPage) ? max(1, min($totalPages, (int) $rawPage)) : 1;

        return $this->render('admin/properties/index.html.twig', [
            'properties' => $properties->findForModeration($term, $agencyId, $commercial, $moderation, $city, $type, $from, $to, $page, $perPage),
            'agencies' => $agencies->findBy([], ['name' => 'ASC']),
            'query' => $term,
            'city' => $city,
            'selectedAgency' => $agencyId ?? '',
            'selectedCommercial' => $commercial?->value ?? '',
            'selectedModeration' => $moderation?->value ?? '',
            'selectedType' => $type?->value ?? '',
            'from' => is_string($request->query->get('from')) ? $request->query->get('from') : '',
            'to' => is_string($request->query->get('to')) ? $request->query->get('to') : '',
            'commercialStatuses' => PropertyStatus::cases(),
            'moderationStatuses' => PropertyModerationStatus::cases(),
            'types' => PropertyType::cases(),
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(Property $property, PropertyStatusHistoryRepository $history, PropertyHighlightRepository $highlights): Response
    {
        return $this->render('admin/properties/show.html.twig', [
            'property' => $property,
            'history' => $history->findForProperty($property),
            'currentHighlight' => $highlights->findCurrentForProperty($property),
            'highlightEligible' => $property->getStatus() === PropertyStatus::PUBLISHED
                && $property->getModerationStatus() === PropertyModerationStatus::APPROVED
                && $property->getAgency()->isActive(),
            'now' => new \DateTimeImmutable(),
            'defaultHighlightStart' => (new \DateTimeImmutable('+3 minutes'))->format('Y-m-d\TH:i'),
            'defaultHighlightEnd' => (new \DateTimeImmutable('+30 days'))->format('Y-m-d\TH:i'),
        ]);
    }

    #[Route('/{id<\d+>}/destaque', name: 'highlight', methods: ['POST'])]
    public function highlight(Property $property, Request $request, EntityManagerInterface $em, PropertyHighlightRepository $highlights, UserAuditLogger $audit): Response
    {
        if (!$this->isCsrfTokenValid('admin-property-highlight-' . $property->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }

        $actor = $this->getUser();
        if (!$actor instanceof User || !$actor->isGlobalAdmin()) {
            throw $this->createAccessDeniedException();
        }

        if ($property->getStatus() !== PropertyStatus::PUBLISHED
            || $property->getModerationStatus() !== PropertyModerationStatus::APPROVED
            || !$property->getAgency()->isActive()) {
            $this->addFlash('error', 'Somente imóveis publicados, aprovados e vinculados a uma imobiliária ativa podem receber destaque.');
            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }

        $reason = $request->request->get('reason');
        $reason = is_string($reason) ? trim($reason) : '';
        $startsRaw = $request->request->get('starts_at');
        $endsRaw = $request->request->get('ends_at');
        $startsAt = $this->parseLocalDateTime($startsRaw);
        $endsAt = $this->parseLocalDateTime($endsRaw);
        $now = new \DateTimeImmutable();

        if ($reason === '' || mb_strlen($reason) > 1000) {
            $this->addFlash('error', 'Informe o motivo do destaque, com até 1000 caracteres.');
            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }
        if ($startsAt === null || $endsAt === null || $startsAt < $now || $endsAt <= $startsAt
            || ($endsAt->getTimestamp() - $startsAt->getTimestamp()) > 90 * 86400 || $endsAt <= $now) {
            $this->addFlash('error', 'Informe datas válidas: o destaque deve começar agora ou no futuro, durar até 90 dias e terminar depois do início.');
            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }

        try {
            $highlights->revokeCurrentForProperty($property, $actor, 'Substituído por um novo período de destaque.');
            $em->persist(new PropertyHighlight($property, $startsAt, $endsAt, $actor, $reason));
            $audit->record($actor, null, $property->getAgency(), 'property_highlight_granted', ['property_id' => $property->getId(), 'reason_code' => 'editorial']);
            $em->flush();
            $this->addFlash('success', 'Destaque editorial registrado. Ele só aparecerá enquanto o anúncio estiver elegível e dentro do período definido.');
        } catch (\DomainException | \InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
    }

    #[Route('/{id<\d+>}/remover-destaque', name: 'remove_highlight', methods: ['POST'])]
    public function removeHighlight(Property $property, Request $request, EntityManagerInterface $em, PropertyHighlightRepository $highlights, UserAuditLogger $audit): Response
    {
        if (!$this->isCsrfTokenValid('admin-property-remove-highlight-' . $property->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }

        $actor = $this->getUser();
        if (!$actor instanceof User || !$actor->isGlobalAdmin()) {
            throw $this->createAccessDeniedException();
        }

        $reason = $request->request->get('reason');
        $reason = is_string($reason) ? trim($reason) : '';
        if ($reason === '' || mb_strlen($reason) > 1000) {
            $this->addFlash('error', 'Informe o motivo da remoção do destaque, com até 1000 caracteres.');
            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }

        try {
            if (!$highlights->revokeCurrentForProperty($property, $actor, $reason)) {
                $this->addFlash('error', 'Este imóvel não possui um destaque vigente ou agendado para remover.');
            } else {
                $audit->record($actor, null, $property->getAgency(), 'property_highlight_removed', ['property_id' => $property->getId(), 'reason_code' => 'admin_removed']);
                $em->flush();
                $this->addFlash('success', 'Destaque removido e decisão registrada.');
            }
        } catch (\DomainException | \InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
    }

    #[Route('/{id<\d+>}/moderacao', name: 'moderate', methods: ['POST'])]
    public function moderate(Property $property, Request $request, EntityManagerInterface $em, PropertyStatusManager $manager, UserAuditLogger $audit): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('admin-property-moderation-' . $property->getId(), $token)) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }
        $actor = $this->getUser();
        if (!$actor instanceof User || !$actor->isGlobalAdmin()) {
            throw $this->createAccessDeniedException();
        }
        $rawStatus = $request->request->get('moderation_status');
        $target = is_string($rawStatus) ? PropertyModerationStatus::tryFrom($rawStatus) : null;
        if (!$target || $target === PropertyModerationStatus::PENDING) {
            $this->addFlash('error', 'A decisão de moderação informada é inválida.');
            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }
        $reason = $request->request->get('reason');
        $reason = is_string($reason) ? trim($reason) : '';
        if (in_array($target, [PropertyModerationStatus::REJECTED, PropertyModerationStatus::CHANGES_REQUESTED], true) && $reason === '') {
            $this->addFlash('error', 'Informe o motivo da rejeição ou dos ajustes solicitados.');
            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }
        if (mb_strlen($reason) > 2000) {
            $this->addFlash('error', 'O motivo deve ter no máximo 2000 caracteres.');
            return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
        }

        try {
            $oldStatus = $property->getModerationStatus()->value;
            $manager->changeModerationStatus($property, $target, $actor, $reason !== '' ? $reason : null);
            $audit->record($actor, null, $property->getAgency(), 'property_moderated', [
                'property_id' => $property->getId(),
                'old_status' => $oldStatus,
                'new_status' => $target->value,
                'reason_code' => 'moderation_decision',
            ]);
            $em->flush();
            $this->addFlash('success', 'Decisão de moderação registrada.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('admin_properties_show', ['id' => $property->getId()]);
    }

    private function parseLocalDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d\TH:i') !== $value) {
            return null;
        }
        return $date;
    }

    private function parseDate(mixed $value, bool $exclusiveEnd): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
            return null;
        }
        return $exclusiveEnd ? $date->modify('+1 day') : $date;
    }
}
