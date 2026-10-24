<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ContactEventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:contact-events:purge',
    description: 'Remove eventos analíticos de contato mais antigos que o período de retenção.'
)]
final class PurgeContactEventsCommand extends Command
{
    public function __construct(private readonly ContactEventRepository $events)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('retention-days', null, InputOption::VALUE_REQUIRED, 'Quantidade de dias para reter eventos.', '365');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = filter_var($input->getOption('retention-days'), FILTER_VALIDATE_INT);
        if ($days === false || $days < 30 || $days > 3650) {
            $output->writeln('<error>O período de retenção deve ser um inteiro entre 30 e 3650 dias.</error>');
            return Command::INVALID;
        }

        $cutoff = new \DateTimeImmutable(sprintf('-%d days', $days));
        $deleted = $this->events->deleteOlderThan($cutoff);
        $output->writeln(sprintf('%d evento(s) removido(s); retenção configurada: %d dias.', $deleted, $days));

        return Command::SUCCESS;
    }
}
