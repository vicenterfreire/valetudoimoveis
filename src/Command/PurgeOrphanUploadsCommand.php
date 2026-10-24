<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Agency;
use App\Entity\PropertyImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:uploads:purge-orphans', description: 'Identifica e remove arquivos de upload sem referência no banco de dados.')]
final class PurgeOrphanUploadsCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        private readonly EntityManagerInterface $entityManager,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('execute', null, InputOption::VALUE_NONE, 'Apaga os arquivos órfãos elegíveis. Sem esta opção, apenas lista o que seria removido.');
        $this->addOption('min-age-hours', null, InputOption::VALUE_REQUIRED, 'Idade mínima do arquivo para considerá-lo órfão.', '24');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $age = filter_var($input->getOption('min-age-hours'), FILTER_VALIDATE_INT);
        if ($age === false || $age < 24 || $age > 8760) {
            $output->writeln('<error>A idade mínima deve ser um inteiro entre 24 e 8760 horas.</error>');
            return Command::INVALID;
        }

        $publicRoot = realpath($this->projectDir . '/public');
        $uploadsRoot = realpath($this->projectDir . '/public/uploads');
        if ($publicRoot === false || $uploadsRoot === false || !str_starts_with($uploadsRoot, $publicRoot . DIRECTORY_SEPARATOR)) {
            $output->writeln('<error>O diretório público de uploads não está disponível ou é inválido.</error>');
            return Command::FAILURE;
        }

        $referenced = [];
        foreach ($this->entityManager->getRepository(PropertyImage::class)->findAll() as $image) {
            $path = ltrim(str_replace('\\', '/', $image->getFilePath()), '/');
            if (preg_match('#^uploads/properties/\d+/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $path)) {
                $referenced[$path] = true;
            }
            $thumbnailPath = ltrim(str_replace('\\', '/', (string) $image->getThumbnailPath()), '/');
            if (preg_match('#^uploads/properties/\d+/thumb_[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $thumbnailPath)) {
                $referenced[$thumbnailPath] = true;
            }
        }
        foreach ($this->entityManager->getRepository(Agency::class)->findAll() as $agency) {
            $path = ltrim(str_replace('\\', '/', (string) $agency->getLogoPath()), '/');
            if (preg_match('#^uploads/agencies/\d+/[a-f0-9]{40}\.png$#D', $path)) {
                $referenced[$path] = true;
            }
        }

        $cutoff = time() - ($age * 3600);
        $candidates = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadsRoot, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->isLink() || $fileInfo->getMTime() > $cutoff) {
                continue;
            }
            $absolute = $fileInfo->getPathname();
            $real = realpath($absolute);
            if ($real === false || !str_starts_with($real, $uploadsRoot . DIRECTORY_SEPARATOR)) {
                continue;
            }
            $relative = ltrim(str_replace('\\', '/', substr($real, strlen($publicRoot))), '/');
            if (!preg_match('#^uploads/(?:properties/\d+/(?:thumb_)?[a-f0-9]{32}\.(?:jpg|png|webp)|agencies/\d+/[a-f0-9]{40}\.png)$#D', $relative)) {
                continue;
            }
            if (!isset($referenced[$relative])) {
                $candidates[] = $real;
            }
        }

        $execute = (bool) $input->getOption('execute');
        $removed = 0;
        $failed = 0;
        foreach ($candidates as $candidate) {
            $output->writeln(($execute ? '<comment>Removendo:</comment> ' : '<info>Órfão:</info> ') . $candidate);
            if (!$execute) {
                continue;
            }
            try {
                $this->filesystem->remove($candidate);
                ++$removed;
            } catch (\Throwable $exception) {
                ++$failed;
                $output->writeln('<error>Falha ao remover arquivo órfão; verifique permissões e o sistema de arquivos.</error>');
            }
        }

        $output->writeln(sprintf('%d arquivo(s) elegível(is). %s', count($candidates), $execute ? sprintf('%d removido(s), %d falha(s).', $removed, $failed) : 'Modo de simulação: nenhum arquivo foi apagado. Use --execute após revisar a lista.'));
        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
