<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Agency;
use App\Repository\AgencyRepository;
use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AgencyPublicController extends AbstractController
{
    #[Route('/imobiliaria/{slug}', name: 'agency_public_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function show(string $slug, AgencyRepository $agencies, PropertyRepository $properties): Response
    {
        $agency = $agencies->findActiveBySlug($slug);
        if (!$agency instanceof Agency) {
            throw $this->createNotFoundException('Imobiliária não encontrada.');
        }

        $whatsappNumber = $this->normalizeBrazilianPhone($agency->getWhatsapp());
        $website = $this->safeWebsite($agency->getWebsite());

        return $this->render('public/agency_show.html.twig', [
            'agency' => $agency,
            'properties' => $properties->findPublicByAgency($agency),
            'whatsappNumber' => $whatsappNumber,
            'website' => $website,
            'publicEmail' => $this->safeEmail($agency->getEmail()),
        ]);
    }

    private function safeEmail(?string $email): ?string
    {
        if ($email === null || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return trim($email);
    }

    private function safeWebsite(?string $website): ?string
    {
        if ($website === null || trim($website) === '') {
            return null;
        }

        $parts = parse_url(trim($website));
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return in_array(strtolower($parts['scheme']), ['http', 'https'], true) ? trim($website) : null;
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
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }
        if (strlen($digits) < 12 || strlen($digits) > 15 || !str_starts_with($digits, '55')) {
            return null;
        }
        return in_array(strlen(substr($digits, 2)), [10, 11], true) ? $digits : null;
    }
}
