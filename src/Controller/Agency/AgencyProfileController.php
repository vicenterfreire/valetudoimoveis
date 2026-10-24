<?php
declare(strict_types=1);

namespace App\Controller\Agency;

use App\Entity\User;
use App\Form\AgencyProfileType;
use App\Repository\AgencyRepository;
use App\Security\AgencyAccess;
use App\Service\AgencyLogoUploader;
use App\Service\Slugger;
use App\Service\UserAuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/painel/perfil', name: 'agency_profile_')]
#[IsGranted('ROLE_AGENCY_MANAGER')]
final class AgencyProfileController extends AbstractController
{
    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        AgencyAccess $access,
        AgencyRepository $agencies,
        EntityManagerInterface $em,
        Slugger $slugger,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $agency = $access->requireManager($user);
        $form = $this->createForm(AgencyProfileType::class, $agency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $agency->setState(strtoupper($agency->getState()));
            $baseSlug = $slugger->slugify($agency->getName());
            $slug = $baseSlug;
            $suffix = 2;
            while ($agencies->slugExists($slug, $agency->getId())) {
                $slug = $baseSlug . '-' . $suffix++;
            }
            $agency->setSlug($slug);
            $em->flush();
            $this->addFlash('success', 'Perfil da imobiliária atualizado.');

            return $this->redirectToRoute('agency_profile_edit');
        }

        return $this->render('agency/profile/edit.html.twig', [
            'form' => $form,
            'agency' => $agency,
        ]);
    }

    #[Route('/logo', name: 'logo', methods: ['POST'])]
    public function updateLogo(
        Request $request,
        AgencyAccess $access,
        AgencyLogoUploader $uploader,
        EntityManagerInterface $em,
        UserAuditLogger $audit,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $agency = $access->requireManager($user);

        if (!$this->isCsrfTokenValid('agency-logo-' . $agency->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }

        $action = (string) $request->request->get('action', 'upload');
        $previousPath = $agency->getLogoPath();

        if ($action === 'remove') {
            $agency->setLogoPath(null);
            $audit->record($user, null, $agency, 'agency_logo_removed', ['file_kind' => 'agency_logo']);
            $em->flush();
            try {
                $uploader->delete($previousPath);
                $this->addFlash('success', 'Logo removido do perfil.');
            } catch (\RuntimeException $exception) {
                $this->addFlash('error', 'O logo foi removido do perfil, mas o arquivo antigo não pôde ser apagado. A rotina de limpeza poderá removê-lo.');
            }
            return $this->redirectToRoute('agency_profile_edit');
        }

        $file = $request->files->get('logo');
        if (!$file instanceof UploadedFile) {
            $this->addFlash('error', 'Selecione uma imagem para o logo.');
            return $this->redirectToRoute('agency_profile_edit');
        }

        try {
            $newPath = $uploader->upload($file, $agency);
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('agency_profile_edit');
        }

        $agency->setLogoPath($newPath);
        $audit->record($user, null, $agency, 'agency_logo_updated', ['file_kind' => 'agency_logo']);
        try {
            $em->flush();
        } catch (\Throwable $exception) {
            $uploader->delete($newPath);
            throw $exception;
        }

        try {
            $uploader->delete($previousPath);
            $this->addFlash('success', 'Logo atualizado.');
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', 'O novo logo foi salvo, mas o arquivo antigo não pôde ser apagado. A rotina de limpeza poderá removê-lo.');
        }
        return $this->redirectToRoute('agency_profile_edit');
    }
}
