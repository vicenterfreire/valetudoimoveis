<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserAuditLogRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:audit-logs:purge', description: 'Remove registros de auditoria anteriores ao prazo de retenção configurado.')]
final class PurgeAuditLogsCommand extends Command
{
    public function __construct(private readonly UserAuditLogRepository $logs) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addOption('retention-days', null, InputOption::VALUE_REQUIRED, 'Dias de retenção dos registros de auditoria (mínimo 365).', '730');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = filter_var($input->getOption('retention-days'), FILTER_VALIDATE_INT);
        if ($days === false || $days < 365 || $days > 3650) {
            $output->writeln('<error>A retenção de auditoria deve ser um inteiro entre 365 e 3650 dias.</error>');
            return Command::INVALID;
        }

        $deleted = $this->logs->deleteOlderThan(new \DateTimeImmutable(sprintf('-%d days', $days)));
        $output->writeln(sprintf('%d registro(s) removido(s); retenção configurada: %d dias.', $deleted, $days));
        return Command::SUCCESS;
    }
}
