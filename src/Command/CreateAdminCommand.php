<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-admin',
    description: 'Cria o usuário administrador inicial do portal.'
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $email = strtolower(trim(
            (string) $io->ask('E-mail do administrador')
        ));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Informe um e-mail válido.');

            return Command::FAILURE;
        }

        if ($this->users->findOneBy(['email' => $email]) !== null) {
            $io->error(
                'Já existe um usuário com esse e-mail. Nenhuma alteração foi feita.'
            );

            return Command::FAILURE;
        }

        $name = trim((string) $io->ask('Nome do administrador'));

        if ($name === '') {
            $io->error('O nome é obrigatório.');

            return Command::FAILURE;
        }

        $password = $io->askHidden(
            'Senha do administrador',
            static fn(?string $value): string =>
            is_string($value) ? $value : ''
        );

        if (!is_string($password) || strlen($password) < 12) {
            $io->error('A senha deve ter pelo menos 12 caracteres.');

            return Command::FAILURE;
        }

        $confirmation = $io->askHidden(
            'Confirme a senha',
            static fn(?string $value): string =>
            is_string($value) ? $value : ''
        );

        if (!is_string($confirmation) || $password !== $confirmation) {
            $io->error('As senhas não coincidem.');

            return Command::FAILURE;
        }

        $admin = new User();
        $admin
            ->setFullName($name)
            ->setEmail($email)
            ->setAgency(null)
            ->setRoles(['ROLE_ADMIN'])
            ->setIsActive(true);

        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, $password)
        );

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Administrador criado com sucesso.');

        return Command::SUCCESS;
    }
}
