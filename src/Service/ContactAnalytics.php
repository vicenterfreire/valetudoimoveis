<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContactEvent;
use App\Entity\Property;
use App\Repository\ContactEventRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ContactAnalytics
{
    public function __construct(
        private readonly ContactEventRepository $events,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function trackView(Request $request, Property $property): void
    {
        $this->track($request, $property, ContactEvent::TYPE_VIEW);
    }

    public function trackContact(Request $request, Property $property, string $channel): void
    {
        $this->track($request, $property, ContactEvent::TYPE_CONTACT, $channel);
    }

    private function track(Request $request, Property $property, string $type, ?string $channel = null): void
    {
        $session = $request->getSession();
        if ($session->get('privacy_analytics_consent') !== true) {
            return;
        }
        $key = sprintf('analytics_%s_%d_%s', $type, $property->getId(), $channel ?? 'none');
        $now = time();
        $last = (int) $session->get($key, 0);

        // Count at most one event of the same kind per session/property/channel in 30 minutes.
        if ($now - $last < 1800) {
            return;
        }

        try {
            $this->events->record($property->getAgency(), $property, $type, $channel);
            $session->set($key, $now);
        } catch (\Throwable $exception) {
            // Analytics must not make public property pages or contact flows unavailable.
            $this->logger->warning('Could not persist property analytics event.', [
                'property_id' => $property->getId(),
                'event_type' => $type,
                'channel' => $channel,
                'exception_class' => $exception::class,
            ]);
        }
    }
}
