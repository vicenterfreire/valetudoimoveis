<?php
declare(strict_types=1);

namespace App\Controller\Agency;

use App\Entity\Property;
use App\Entity\PropertyImage;
use App\Entity\User;
use App\Enum\PropertyStatus;
use App\Form\PropertyTypeForm;
use App\Repository\PropertyRepository;
use App\Repository\PropertyStatusHistoryRepository;
use App\Security\AgencyAccess;
use App\Service\PropertyImageUploader;
use App\Service\PropertyStatusManager;
use App\Service\UserAuditLogger;
use App\Service\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/painel/imoveis', name: 'agency_properties_')]
#[IsGranted('ROLE_AGENCY_STAFF')]
class PropertyController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(AgencyAccess $access, PropertyRepository $repository, PropertyStatusHistoryRepository $history, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $agency = $access->requireActiveAgency($user);

        $properties = $repository->findForAgency($agency, $request->query->get('q'));
        return $this->render('agency/properties/index.html.twig', [
            'properties' => $properties,
            'moderationHistory' => $history->findLatestModerationForProperties($properties),
            'query' => $request->query->get('q', ''),
        ]);
    }

    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, AgencyAccess $access, EntityManagerInterface $em, Slugger $slugger, PropertyRepository $properties): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $property = (new Property())->setAgency($access->requireActiveAgency($user));
        $form = $this->createForm(PropertyTypeForm::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $property->setSlug($this->uniqueSlug($slugger, $properties, $property));
            $property->touch();
            $em->persist($property);
            $em->flush();
            $this->addFlash('success', 'Imóvel criado como rascunho. Agora você pode adicionar fotos.');

            return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
        }

        return $this->render('agency/properties/form.html.twig', [
            'form' => $form,
            'heading' => 'Cadastrar imóvel',
            'property' => $property,
        ]);
    }

    #[Route('/{id<\d+>}/editar', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Property $property, Request $request, AgencyAccess $access, EntityManagerInterface $em, Slugger $slugger, PropertyRepository $properties, PropertyStatusManager $statusManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);

        $form = $this->createForm(PropertyTypeForm::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $property->setSlug($this->uniqueSlug($slugger, $properties, $property));
            $property->touch();
            $this->submitForModerationAfterAgencyChange($property, $user, $statusManager);
            $em->flush();
            $this->addFlash('success', 'Imóvel atualizado. Se já estava aprovado, a alteração foi encaminhada para nova análise.');

            return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
        }

        return $this->render('agency/properties/form.html.twig', [
            'form' => $form,
            'heading' => 'Editar imóvel',
            'property' => $property,
        ]);
    }

    #[Route('/{id<\d+>}/imagens', name: 'images_upload', methods: ['POST'])]
    public function uploadImages(
        Property $property,
        Request $request,
        AgencyAccess $access,
        PropertyImageUploader $uploader,
        EntityManagerInterface $em,
        PropertyStatusManager $statusManager,
        UserAuditLogger $audit,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);

        if (!$this->isCsrfTokenValid('property-images-' . $property->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $files = $request->files->all('images');
        if (!is_array($files)) {
            $files = [];
        }
        $files = array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));

        if ($files === []) {
            $this->addFlash('error', 'Selecione pelo menos uma imagem.');

            return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
        }

        $maxBatchFiles = 10;
        $maxBatchBytes = 40 * 1024 * 1024;
        $maxImagesPerProperty = 30;
        if (count($files) > $maxBatchFiles) {
            $this->addFlash('error', sprintf('Envie no máximo %d fotos por vez.', $maxBatchFiles));
            return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
        }
        $batchBytes = array_sum(array_map(static fn (UploadedFile $file): int => max(0, (int) ($file->getSize() ?? 0)), $files));
        if ($batchBytes > $maxBatchBytes) {
            $this->addFlash('error', 'O lote excede 40 MB. Divida as fotos em envios menores.');
            return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
        }
        if (count($property->getImages()) + count($files) > $maxImagesPerProperty) {
            $this->addFlash('error', sprintf('Cada imóvel pode ter no máximo %d fotos. Você já possui %d.', $maxImagesPerProperty, count($property->getImages())));
            return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
        }

        $order = count($property->getImages());
        $uploaded = [];
        $errors = [];
        foreach ($files as $index => $file) {
            try {
                $image = $uploader->upload($file, $property, $order);
                $property->addImage($image);
                $uploaded[] = $image;
                ++$order;
            } catch (\InvalidArgumentException | \RuntimeException $exception) {
                $errors[] = sprintf('%s: %s', $file->getClientOriginalName() ?: 'Arquivo ' . ($index + 1), $exception->getMessage());
            }
        }

        if ($uploaded !== []) {
            try {
                $property->touch();
                $this->submitForModerationAfterAgencyChange($property, $user, $statusManager);
                $audit->record($user, null, $property->getAgency(), 'property_images_uploaded', [
                    'property_id' => $property->getId(),
                    'image_count' => count($uploaded),
                    'file_kind' => 'property_image',
                ]);
                $em->flush();
                $this->addFlash('success', sprintf('%d foto(s) adicionada(s).', count($uploaded)));
            } catch (\Throwable $exception) {
                foreach ($uploaded as $image) {
                    $property->removeImage($image);
                    $uploader->deleteFile($image, $property->getId());
                }
                throw $exception;
            }
        }
        foreach (array_slice($errors, 0, 5) as $error) {
            $this->addFlash('error', $error);
        }
        if (count($errors) > 5) {
            $this->addFlash('error', sprintf('E mais %d arquivo(s) não puderam ser processados.', count($errors) - 5));
        }
        if ($uploaded === [] && $errors === []) {
            $this->addFlash('error', 'Nenhuma foto foi enviada. Confira os arquivos selecionados.');
        }

        return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
    }

    #[Route('/{id<\d+>}/imagens/{imageId<\d+>}/texto-alternativo', name: 'images_alt', methods: ['POST'])]
    public function updateImageAltText(Property $property, int $imageId, Request $request, AgencyAccess $access, EntityManagerInterface $em, PropertyStatusManager $statusManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);
        if (!$this->isCsrfTokenValid('alt-property-image-' . $imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $image = $this->findPropertyImage($property, $imageId);
        $altText = trim((string) $request->request->get('altText', ''));
        if (mb_strlen($altText) > 180) {
            $this->addFlash('error', 'O texto alternativo deve ter no máximo 180 caracteres.');
            return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
        }

        $image->setAltText($altText === '' ? null : $altText);
        $property->touch();
        $this->submitForModerationAfterAgencyChange($property, $user, $statusManager);
        $em->flush();
        $this->addFlash('success', 'Texto alternativo atualizado.');
        return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
    }

    #[Route('/{id<\d+>}/imagens/{imageId<\d+>}/ordem', name: 'images_reorder', methods: ['POST'])]
    public function reorderImage(Property $property, int $imageId, Request $request, AgencyAccess $access, EntityManagerInterface $em, PropertyStatusManager $statusManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);
        if (!$this->isCsrfTokenValid('reorder-property-image-' . $imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $image = $this->findPropertyImage($property, $imageId);
        $direction = (string) $request->request->get('direction');
        if (!in_array($direction, ['up', 'down'], true)) {
            throw $this->createAccessDeniedException();
        }

        $images = $property->getImages()->toArray();
        usort($images, static fn (PropertyImage $a, PropertyImage $b): int => [$a->getSortOrder(), $a->getId() ?? 0] <=> [$b->getSortOrder(), $b->getId() ?? 0]);
        $position = array_search($image, $images, true);
        if ($position !== false) {
            $target = $direction === 'up' ? $position - 1 : $position + 1;
            if (isset($images[$target])) {
                [$images[$position], $images[$target]] = [$images[$target], $images[$position]];
                foreach ($images as $index => $orderedImage) {
                    $orderedImage->setSortOrder($index);
                }
                $property->touch();
                $this->submitForModerationAfterAgencyChange($property, $user, $statusManager);
                $em->flush();
                $this->addFlash('success', 'Ordem das fotos atualizada.');
            }
        }
        return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
    }

    #[Route('/{id<\d+>}/imagens/{imageId<\d+>}/remover', name: 'images_delete', methods: ['POST'])]
    public function deleteImage(
        Property $property,
        int $imageId,
        Request $request,
        AgencyAccess $access,
        PropertyImageUploader $uploader,
        EntityManagerInterface $em,
        PropertyStatusManager $statusManager,
        UserAuditLogger $audit,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);

        if (!$this->isCsrfTokenValid('delete-property-image-' . $imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $image = null;
        foreach ($property->getImages() as $candidate) {
            if ($candidate instanceof PropertyImage && $candidate->getId() === $imageId) {
                $image = $candidate;
                break;
            }
        }
        if (!$image instanceof PropertyImage) {
            throw $this->createNotFoundException('Imagem não encontrada neste imóvel.');
        }

        $filePath = $image->getFilePath();
        $property->removeImage($image);
        $property->touch();
        $this->submitForModerationAfterAgencyChange($property, $user, $statusManager);
        $em->remove($image);
        $audit->record($user, null, $property->getAgency(), 'property_image_deleted', [
            'property_id' => $property->getId(),
            'file_kind' => 'property_image',
        ]);
        $em->flush();
        try {
            $uploader->deleteFile($image, $property->getId());
            $this->addFlash('success', 'Imagem removida.');
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', 'O registro foi removido, mas o arquivo físico não pôde ser apagado. Ele será identificado pela rotina de limpeza.');
        }

        return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
    }

    #[Route('/{id<\d+>}/imagens/{imageId<\d+>}/capa', name: 'images_cover', methods: ['POST'])]
    public function setCover(
        Property $property,
        int $imageId,
        Request $request,
        AgencyAccess $access,
        EntityManagerInterface $em,
        PropertyStatusManager $statusManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);

        if (!$this->isCsrfTokenValid('cover-property-image-' . $imageId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $images = $property->getImages()->toArray();
        $selectedImage = null;
        foreach ($images as $image) {
            if ($image instanceof PropertyImage && $image->getId() === $imageId) {
                $selectedImage = $image;
                break;
            }
        }
        if (!$selectedImage instanceof PropertyImage) {
            throw $this->createNotFoundException('Imagem não encontrada neste imóvel.');
        }

        usort($images, static function (PropertyImage $left, PropertyImage $right): int {
            return [$left->getSortOrder(), $left->getId() ?? 0] <=> [$right->getSortOrder(), $right->getId() ?? 0];
        });
        $images = array_values(array_filter($images, static fn (PropertyImage $image): bool => $image !== $selectedImage));
        array_unshift($images, $selectedImage);
        foreach ($images as $position => $image) {
            $image->setSortOrder($position);
        }
        $property->touch();
        $this->submitForModerationAfterAgencyChange($property, $user, $statusManager);

        $em->flush();
        $this->addFlash('success', 'Foto de capa atualizada.');

        return $this->redirectToRoute('agency_properties_edit', ['id' => $property->getId()]);
    }

    #[Route('/{id<\d+>}/status/{status}', name: 'status', methods: ['POST'])]
    public function status(Property $property, string $status, Request $request, AgencyAccess $access, EntityManagerInterface $em, PropertyStatusManager $statusManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);

        if (!$this->isCsrfTokenValid('property-status-' . $property->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $newStatus = PropertyStatus::tryFrom($status);
        if (!$newStatus || ($newStatus === PropertyStatus::ARCHIVED && !$user->isAgencyManager())) {
            throw $this->createAccessDeniedException();
        }

        try {
            $reasonRaw = $request->request->get('reason');
            $reason = is_string($reasonRaw) ? $reasonRaw : null;
            $statusManager->transition($property, $newStatus, $user, $reason);
            $em->flush();
            $this->addFlash('success', 'Status comercial atualizado.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('agency_properties_index');
    }

    #[Route('/{id<\d+>}/solicitar-revisao', name: 'request_review', methods: ['POST'])]
    public function requestReview(Property $property, Request $request, AgencyAccess $access, EntityManagerInterface $em, PropertyStatusManager $statusManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $access->assertCanManageProperty($user, $property);
        if (!$this->isCsrfTokenValid('property-review-' . $property->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }
        if (!in_array($property->getModerationStatus(), [\App\Enum\PropertyModerationStatus::REJECTED, \App\Enum\PropertyModerationStatus::CHANGES_REQUESTED], true)) {
            $this->addFlash('error', 'Só é possível solicitar nova análise após uma rejeição ou pedido de ajustes.');
            return $this->redirectToRoute('agency_properties_index');
        }
        $statusManager->changeModerationStatus($property, \App\Enum\PropertyModerationStatus::PENDING, $user, 'A imobiliária solicitou nova análise.');
        $em->flush();
        $this->addFlash('success', 'Imóvel enviado novamente para análise. Ele só poderá ser publicado após aprovação.');
        return $this->redirectToRoute('agency_properties_index');
    }

    private function submitForModerationAfterAgencyChange(Property $property, User $actor, PropertyStatusManager $statusManager): void
    {
        if ($property->getModerationStatus() === \App\Enum\PropertyModerationStatus::APPROVED) {
            $statusManager->changeModerationStatus(
                $property,
                \App\Enum\PropertyModerationStatus::PENDING,
                $actor,
                'Conteúdo do anúncio alterado pela imobiliária; nova análise necessária.',
            );
        }
    }

    private function findPropertyImage(Property $property, int $imageId): PropertyImage
    {
        foreach ($property->getImages() as $image) {
            if ($image instanceof PropertyImage && $image->getId() === $imageId) {
                return $image;
            }
        }

        throw $this->createNotFoundException('Imagem não encontrada neste imóvel.');
    }

    private function uniqueSlug(Slugger $slugger, PropertyRepository $repository, Property $property): string
    {
        $base = $slugger->slugify($property->getSlug() ?: $property->getTitle());
        $slug = $base;
        $suffix = 2;
        while ($repository->slugExists($slug, $property->getId())) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
