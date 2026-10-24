<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Property;
use App\Entity\PropertyImage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PropertyImageUploader
{
    public const MAX_UPLOAD_BYTES = 8 * 1024 * 1024;
    public const MAX_DIMENSION = 2400;
    public const THUMBNAIL_MAX_DIMENSION = 480;
    public const MAX_PIXELS = 40000000;

    public function __construct(
        private string $projectDir,
        private Filesystem $filesystem,
    ) {
    }

    public function upload(UploadedFile $file, Property $property, int $order = 0): PropertyImage
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('O envio da imagem não foi concluído corretamente.');
        }

        if (($file->getSize() ?? 0) > self::MAX_UPLOAD_BYTES) {
            throw new \InvalidArgumentException('Imagem excede 8 MB.');
        }

        if (!$property->getId()) {
            throw new \LogicException('Salve o imóvel antes de enviar imagens.');
        }

        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false || !isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])) {
            throw new \InvalidArgumentException('O arquivo enviado não contém uma imagem válida.');
        }

        [$width, $height] = [$imageInfo[0], $imageInfo[1]];
        $mime = $imageInfo['mime'];
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($types[$mime])) {
            throw new \InvalidArgumentException('Use JPG, PNG ou WEBP.');
        }
        if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || $width * $height > self::MAX_PIXELS) {
            throw new \InvalidArgumentException('A imagem possui dimensões muito grandes. Reduza a resolução e tente novamente.');
        }
        if (!function_exists('imagecreatefromstring')) {
            throw new \RuntimeException('A otimização de imagens requer a extensão GD do PHP habilitada no servidor.');
        }

        $binary = @file_get_contents($file->getPathname());
        $source = $binary === false ? false : @imagecreatefromstring($binary);
        if ($source === false) {
            throw new \InvalidArgumentException('Não foi possível processar esta imagem. Tente exportá-la novamente como JPG, PNG ou WEBP.');
        }

        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($target === false) {
            imagedestroy($source);
            throw new \RuntimeException('Não foi possível preparar a imagem para otimização.');
        }

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        if (!imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
            imagedestroy($source);
            imagedestroy($target);
            throw new \RuntimeException('Falha ao redimensionar a imagem.');
        }

        $dir = rtrim($this->projectDir, '/\\') . '/public/uploads/properties/' . $property->getId();
        $destination = null;
        try {
            $this->filesystem->mkdir($dir, 0755);
            $name = bin2hex(random_bytes(16)) . '.' . $types[$mime];
            $destination = $dir . '/' . $name;
            $saved = match ($mime) {
                'image/jpeg' => imagejpeg($target, $destination, 82),
                'image/png' => imagepng($target, $destination, 7),
                'image/webp' => function_exists('imagewebp') ? imagewebp($target, $destination, 80) : false,
            };
            if (!$saved) {
                throw new \RuntimeException('Não foi possível salvar a versão otimizada da imagem.');
            }
            $thumbnailName = 'thumb_' . $name;
            $thumbnailDestination = $dir . '/' . $thumbnailName;
            $thumbnailScale = min(1, self::THUMBNAIL_MAX_DIMENSION / max($targetWidth, $targetHeight));
            $thumbnailWidth = max(1, (int) round($targetWidth * $thumbnailScale));
            $thumbnailHeight = max(1, (int) round($targetHeight * $thumbnailScale));
            $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
            if ($thumbnail === false) {
                throw new \RuntimeException('Não foi possível preparar a miniatura.');
            }
            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
                imagefilledrectangle($thumbnail, 0, 0, $thumbnailWidth, $thumbnailHeight, $transparent);
            }
            $thumbnailCopied = imagecopyresampled($thumbnail, $target, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $targetWidth, $targetHeight);
            $thumbnailSaved = $thumbnailCopied && match ($mime) {
                'image/jpeg' => imagejpeg($thumbnail, $thumbnailDestination, 76),
                'image/png' => imagepng($thumbnail, $thumbnailDestination, 8),
                'image/webp' => function_exists('imagewebp') ? imagewebp($thumbnail, $thumbnailDestination, 72) : false,
            };
            imagedestroy($thumbnail);
            if (!$thumbnailSaved) {
                throw new \RuntimeException('Não foi possível gerar a miniatura da imagem.');
            }
        } catch (\Throwable $exception) {
            imagedestroy($source);
            imagedestroy($target);
            if (is_string($destination) && is_file($destination)) {
                $this->filesystem->remove($destination);
            }
            if (isset($thumbnailDestination) && is_file($thumbnailDestination)) {
                $this->filesystem->remove($thumbnailDestination);
            }
            throw new \RuntimeException('Não foi possível gravar a imagem processada e sua miniatura.', 0, $exception);
        }

        imagedestroy($source);
        imagedestroy($target);

        if (!$saved) {
            if (is_string($destination) && is_file($destination)) {
                $this->filesystem->remove($destination);
            }
            if (isset($thumbnailDestination) && is_file($thumbnailDestination)) {
                $this->filesystem->remove($thumbnailDestination);
            }
            throw new \RuntimeException('Não foi possível salvar a imagem e sua miniatura. Verifique o suporte a GD/WEBP no PHP.');
        }

        $image = new PropertyImage();
        $image->setFilePath('uploads/properties/' . $property->getId() . '/' . $name);
        $image->setThumbnailPath('uploads/properties/' . $property->getId() . '/thumb_' . $name);
        $image->setSortOrder($order);
        $image->setProperty($property);

        return $image;
    }

    public function deleteFile(PropertyImage $image, ?int $authorizedPropertyId = null): void
    {
        $propertyId = $authorizedPropertyId ?? $image->getProperty()?->getId();
        $relativePath = $image->getFilePath();
        if (!$propertyId || !preg_match('#^uploads/properties/' . preg_quote((string) $propertyId, '#') . '/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $relativePath)) {
            throw new \RuntimeException('O caminho da imagem não corresponde ao padrão de arquivos gerados pelo sistema.');
        }

        $publicRoot = realpath(rtrim($this->projectDir, '/\\') . '/public');
        $propertyDirectoryPath = rtrim($this->projectDir, '/\\') . '/public/uploads/properties/' . $propertyId;
        if (is_link($propertyDirectoryPath)) {
            throw new \RuntimeException('Diretórios simbólicos não podem ser usados para remover imagens.');
        }
        $propertyDirectory = realpath($propertyDirectoryPath);
        if ($publicRoot === false || $propertyDirectory === false || !str_starts_with($propertyDirectory, $publicRoot . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException('O diretório da imagem não está disponível ou é inválido.');
        }

        $path = $propertyDirectory . DIRECTORY_SEPARATOR . basename($relativePath);
        if (is_link($path)) {
            throw new \RuntimeException('Links simbólicos não podem ser removidos como imagens de anúncio.');
        }
        if (is_file($path)) {
            $this->filesystem->remove($path);
        }
        $thumbnailPath = $image->getThumbnailPath();
        if ($thumbnailPath !== null) {
            $expectedThumbnail = 'uploads/properties/' . $propertyId . '/thumb_' . basename($relativePath);
            if ($thumbnailPath !== $expectedThumbnail) {
                throw new \RuntimeException('O caminho da miniatura não corresponde ao padrão de arquivos gerados pelo sistema.');
            }
            $thumbnailAbsolute = $propertyDirectory . DIRECTORY_SEPARATOR . basename($thumbnailPath);
            if (is_link($thumbnailAbsolute)) {
                throw new \RuntimeException('Links simbólicos não podem ser removidos como miniaturas.');
            }
            if (is_file($thumbnailAbsolute)) {
                $this->filesystem->remove($thumbnailAbsolute);
            }
        }
    }
}
