<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccountActionToken;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserAuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\AccountActionService;

final class AccountSecurityController extends AbstractController
{
    #[Route('/senha/esqueci', name: 'account_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $users,
        AccountActionService $actions,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot-password', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token de segurança inválido.');
            }

            $email = $request->request->get('email');
            if (is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                $user = $users->findOneBy(['email' => mb_strtolower(trim($email))]);
                if ($user instanceof User && $user->isActive() && ($user->isGlobalAdmin() || $user->getAgency()?->isActive())) {
                    try {
                        $actions->requestPasswordReset($user);
                    } catch (TransportExceptionInterface|\RuntimeException $exception) {
                        // Keep the response generic to avoid account enumeration and exposing mail infrastructure.
                    }
                }
            }

            $this->addFlash('success', 'Se houver uma conta ativa com esse e-mail, enviaremos instruções para redefinir a senha.');
            return $this->redirectToRoute('account_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/senha/redefinir/{token}', name: 'account_reset_password', methods: ['GET', 'POST'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserAuditLogger $audit,
    ): Response {
        $actionToken = $em->getRepository(AccountActionToken::class)->findOneBy(['tokenHash' => hash('sha256', $token)]);
        if (!$actionToken instanceof AccountActionToken || !$actionToken->isUsable(new \DateTimeImmutable(), AccountActionToken::PASSWORD_RESET)) {
            throw $this->createNotFoundException('O link de redefinição é inválido ou expirou.');
        }

        $tokenUser = $actionToken->getUser();
        // A manager may define a password while their agency is pending review.
        // UserChecker still blocks authentication until the agency is approved.
        if (!$tokenUser->isActive()) {
            throw $this->createNotFoundException('O acesso desta conta está indisponível.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset-password-' . $token, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token de segurança inválido.');
            }

            $password = $request->request->get('password');
            $confirmation = $request->request->get('password_confirmation');
            if (!is_string($password) || strlen($password) < 12 || strlen($password) > 4096) {
                $this->addFlash('error', 'A senha deve ter pelo menos 12 caracteres.');
            } elseif (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
                $this->addFlash('error', 'A confirmação da senha não corresponde.');
            } else {
                $user = $actionToken->getUser();
                $user->setPassword($hasher->hashPassword($user, $password));
                $actionToken->markUsed();
                foreach ($em->getRepository(AccountActionToken::class)->findBy(['user' => $user, 'type' => AccountActionToken::PASSWORD_RESET]) as $otherToken) {
                    if ($otherToken instanceof AccountActionToken) {
                        $otherToken->markUsed();
                    }
                }
                $audit->record(null, $user, $user->getAgency(), 'password_reset_completed');
                $em->flush();
                $this->addFlash('success', 'Senha redefinida. Entre com sua nova senha.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }

    #[Route('/conta/confirmar-email/{token}', name: 'account_confirm_email', methods: ['GET', 'POST'], requirements: ['token' => '[a-f0-9]{64}'])]
    public function confirmEmail(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        UserAuditLogger $audit,
    ): Response {
        $actionToken = $em->getRepository(AccountActionToken::class)->findOneBy(['tokenHash' => hash('sha256', $token)]);
        if (!$actionToken instanceof AccountActionToken || !$actionToken->isUsable(new \DateTimeImmutable(), AccountActionToken::EMAIL_CHANGE)) {
            throw $this->createNotFoundException('O link de confirmação é inválido ou expirou.');
        }

        $tokenUser = $actionToken->getUser();
        if (!$tokenUser->isActive() || (!$tokenUser->isGlobalAdmin() && !$tokenUser->getAgency()?->isActive())) {
            throw $this->createNotFoundException('O acesso desta conta está indisponível.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('confirm-email-' . $token, (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token de segurança inválido.');
            }

            $newEmail = $actionToken->getRequestedEmail();
            if ($newEmail === null || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw $this->createNotFoundException('A solicitação de alteração de e-mail é inválida.');
            }

            $duplicate = $users->findOneBy(['email' => mb_strtolower($newEmail)]);
            if ($duplicate && $duplicate->getId() !== $actionToken->getUser()->getId()) {
                $this->addFlash('error', 'Este e-mail já está vinculado a outra conta.');
            } else {
                $user = $actionToken->getUser();
                $user->setEmail($newEmail);
                $actionToken->markUsed();
                $audit->record(null, $user, $user->getAgency(), 'email_change_confirmed');
                $em->flush();
                $this->addFlash('success', 'E-mail confirmado e atualizado. Use o novo endereço no próximo acesso.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/confirm_email.html.twig', [
            'token' => $token,
            'newEmail' => $actionToken->getRequestedEmail(),
        ]);
    }
}
