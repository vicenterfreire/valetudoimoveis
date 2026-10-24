<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PrivacyController extends AbstractController
{
    #[Route('/privacidade', name: 'privacy_notice', methods: ['GET'])]
    public function notice(): Response
    {
        return $this->render('public/privacy.html.twig');
    }

    #[Route('/privacidade/consentimento', name: 'privacy_consent', methods: ['POST'])]
    public function consent(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('privacy-analytics-consent', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Não foi possível validar sua preferência de privacidade.');
        }

        $choice = $request->request->get('choice');
        if (!in_array($choice, ['accept', 'reject'], true)) {
            throw $this->createNotFoundException('Preferência de privacidade inválida.');
        }

        $request->getSession()->set('privacy_analytics_consent', $choice === 'accept');
        $this->addFlash('success', $choice === 'accept'
            ? 'Você permitiu métricas agregadas de utilização. Pode alterar essa escolha na página de privacidade.'
            : 'Métricas opcionais desativadas nesta sessão.');

        return $this->redirectToRoute('privacy_notice');
    }
}
