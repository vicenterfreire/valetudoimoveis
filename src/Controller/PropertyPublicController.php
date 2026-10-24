<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Property;
use App\Entity\ContactEvent;
use App\Repository\PropertyRepository;
use App\Service\ContactAnalytics;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class PropertyPublicController extends AbstractController
{
    #[Route('/imovel/{id<\d+>}/{slug}', name: 'property_show', methods: ['GET'])]
    public function show(int $id, string $slug, PropertyRepository $repo, Request $request, ContactAnalytics $analytics): Response
    {
        $property = $repo->findPublicById($id);
        if (!$property instanceof Property) {
            throw $this->createNotFoundException('Imóvel não encontrado.');
        }

        if ($slug !== $property->getSlug()) {
            return $this->redirectToRoute('property_show', [
                'id' => $property->getId(),
                'slug' => $property->getSlug(),
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        $analytics->trackView($request, $property);

        return $this->render('public/property_show.html.twig', [
            'property' => $property,
            'similarProperties' => $repo->findSimilarPublic($property, 3),
            'whatsappNumber' => $this->normalizeBrazilianPhone($property->getAgency()->getWhatsapp()),
        ]);
    }

    #[Route('/imovel/{id<\d+>}/{slug}/contato/whatsapp', name: 'property_contact_whatsapp', methods: ['POST'])]
    public function whatsappContact(
        int $id,
        string $slug,
        Request $request,
        PropertyRepository $repo,
        ContactAnalytics $analytics,
    ): Response {
        $property = $repo->findPublicById($id);
        if (!$property instanceof Property) {
            throw $this->createNotFoundException('Imóvel não encontrado.');
        }

        if ($slug !== $property->getSlug()) {
            return $this->redirectToRoute('property_show', [
                'id' => $property->getId(),
                'slug' => $property->getSlug(),
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        if (!$this->isCsrfTokenValid('property_contact_whatsapp_' . $property->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Não foi possível validar a solicitação de contato. Atualize a página e tente novamente.');
        }

        $phone = $this->normalizeBrazilianPhone($property->getAgency()->getWhatsapp());
        if ($phone === null) {
            $this->addFlash('error', 'Esta imobiliária não possui um WhatsApp válido cadastrado. Utilize os outros meios de contato disponíveis.');
            return $this->redirectToRoute('property_show', ['id' => $property->getId(), 'slug' => $property->getSlug()]);
        }

        $analytics->trackContact($request, $property, ContactEvent::CHANNEL_WHATSAPP);

        $message = sprintf('Olá, tenho interesse no imóvel %s (código %d).', $property->getTitle(), $property->getId());
        return new RedirectResponse('https://wa.me/' . $phone . '?text=' . rawurlencode($message));
    }

    private function normalizeBrazilianPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        // Brazilian local numbers are stored as 10/11 digits; add country code.
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }

        // wa.me accepts international digits only, without a leading plus sign.
        if (strlen($digits) < 12 || strlen($digits) > 15 || !str_starts_with($digits, '55')) {
            return null;
        }

        $nationalNumber = substr($digits, 2);
        if (!in_array(strlen($nationalNumber), [10, 11], true)) {
            return null;
        }

        return $digits;
    }
}
