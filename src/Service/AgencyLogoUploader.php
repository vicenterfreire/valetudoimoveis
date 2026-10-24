<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Agency;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AgencyLogoUploader
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_DIMENSION = 1600;
    private const MAX_PIXELS = 20_000_000;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function upload(UploadedFile $file, Agency $agency): string
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('O envio do logo falhou. Tente novamente.');
        }

        $size = $file->getSize();
        if (!is_int($size) || $size < 1 || $size > self::MAX_BYTES) {
            throw new \InvalidArgumentException('O logo deve ter até 5 MB.');
        }

        $temporaryPath = $file->getPathname();
        $imageInfo = @getimagesize($temporaryPath);
        if (!is_array($imageInfo) || !isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])) {
            throw new \InvalidArgumentException('O arquivo enviado não é uma imagem válida.');
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $mime = (string) $imageInfo['mime'];
        if ($width < 1 || $height < 1 || $width > self::MAX_PIXELS / $height) {
            throw new \InvalidArgumentException('As dimensões do logo são inválidas ou muito grandes.');
        }
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \InvalidArgumentException('Use uma imagem JPEG, PNG ou WebP.');
        }
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('O servidor não possui a extensão GD necessária para processar imagens.');
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($temporaryPath),
            'image/png' => @imagecreatefrompng($temporaryPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($temporaryPath) : false,
            default => false,
        };
        if (!$source instanceof \GdImage) {
            throw new \InvalidArgumentException('Não foi possível decodificar a imagem enviada.');
        }

        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) floor($width * $scale));
        $targetHeight = max(1, (int) floor($height * $scale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$canvas instanceof \GdImage) {
            imagedestroy($source);
            throw new \RuntimeException('Não foi possível processar o logo.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $directory = $this->projectDir . '/public/uploads/agencies/' . $agency->getId();
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            imagedestroy($source);
            imagedestroy($canvas);
            throw new \RuntimeException('Não foi possível criar o diretório do logo.');
        }

        $filename = bin2hex(random_bytes(20)) . '.png';
        $absolutePath = $directory . '/' . $filename;
        $saved = imagepng($canvas, $absolutePath, 7);
        imagedestroy($source);
        imagedestroy($canvas);

        if (!$saved) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            throw new \RuntimeException('Não foi possível salvar o logo processado.');
        }

        return '/uploads/agencies/' . $agency->getId() . '/' . $filename;
    }

    public function delete(?string $publicPath): void
    {
        if ($publicPath === null || !preg_match('#^/uploads/agencies/\d+/[a-f0-9]{40}\.png$#', $publicPath)) {
            return;
        }

        $publicRoot = realpath($this->projectDir . '/public');
        $directoryPath = $this->projectDir . '/public/uploads/agencies/' . basename(dirname($publicPath));
        if (is_link($directoryPath)) {
            throw new \RuntimeException('Diretórios simbólicos não podem ser usados para remover logos.');
        }
        $directory = realpath($directoryPath);
        if ($publicRoot === false || $directory === false || !str_starts_with($directory, $publicRoot . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('O diretório do logo não está disponível ou é inválido.');
        }

        $absolutePath = $directory . DIRECTORY_SEPARATOR . basename($publicPath);
        if (is_link($absolutePath)) {
            throw new \RuntimeException('Links simbólicos não podem ser removidos como logos.');
        }
        if (is_file($absolutePath) && !unlink($absolutePath)) {
            throw new \RuntimeException('Não foi possível remover o logo anterior.');
        }
    }
}
