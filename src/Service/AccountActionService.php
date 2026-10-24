<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AccountActionToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AccountActionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function requestEmailChange(User $user, string $newEmail): void
    {
        $this->issueAndSend($user, AccountActionToken::EMAIL_CHANGE, $newEmail);
    }

    public function requestPasswordReset(User $user): void
    {
        $this->issueAndSend($user, AccountActionToken::PASSWORD_RESET, null);
    }

    private function issueAndSend(User $user, string $type, ?string $requestedEmail): void
    {
        foreach ($this->entityManager->getRepository(AccountActionToken::class)->findBy(['user' => $user, 'type' => $type]) as $oldToken) {
            if ($oldToken instanceof AccountActionToken) {
                $oldToken->markUsed();
            }
        }

        $plainToken = bin2hex(random_bytes(32));
        $token = new AccountActionToken(
            $user,
            $type,
            hash('sha256', $plainToken),
            $requestedEmail,
            new \DateTimeImmutable('+1 hour'),
        );
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $isEmailChange = $type === AccountActionToken::EMAIL_CHANGE;
        $url = $this->urlGenerator->generate(
            $isEmailChange ? 'account_confirm_email' : 'account_reset_password',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $recipient = $isEmailChange ? (string) $requestedEmail : $user->getEmail();
        $subject = $isEmailChange ? 'Confirme a alteração do e-mail — ValeTudo Imóveis' : 'Recuperação de senha — ValeTudo Imóveis';
        $body = $isEmailChange
            ? "Foi solicitada a alteração do e-mail da sua conta ValeTudo Imóveis. Confirme o novo endereço acessando o link abaixo em até 1 hora:\n\n{$url}\n\nSe você não solicitou esta alteração, ignore esta mensagem."
            : "Recebemos uma solicitação de redefinição de senha para sua conta ValeTudo Imóveis. Defina uma nova senha acessando o link abaixo em até 1 hora:\n\n{$url}\n\nSe você não solicitou esta alteração, ignore esta mensagem. Sua senha atual continuará válida.";

        $from = $_SERVER['MAILER_FROM'] ?? $_ENV['MAILER_FROM'] ?? getenv('MAILER_FROM');
        if (!is_string($from) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $from = 'no-reply@localhost';
        }
        $this->mailer->send((new Email())->from($from)->to($recipient)->subject($subject)->text($body));
    }
}
