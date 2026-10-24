<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Entity\AgencyStatusHistory;
use App\Entity\User;
use App\Enum\AgencyStatus;
use App\Form\AgencyType;
use App\Repository\AgencyRepository;
use App\Service\AgencyStatusManager;
use App\Service\Slugger;
use App\Service\UserAuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/imobiliarias', name: 'admin_agencies_')]
#[IsGranted('ROLE_ADMIN')]
class AgencyAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, AgencyRepository $repo): Response
    {
        $query = $request->query->get('q', '');
        $query = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $rawStatus = $request->query->get('status', '');
        $status = is_string($rawStatus) && $rawStatus !== '' ? AgencyStatus::tryFrom($rawStatus) : null;

        $perPage = 25;
        $totalItems = $repo->countForAdmin($query, $status);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $rawPage = $request->query->get('page', '1');
        $page = is_scalar($rawPage) && ctype_digit((string) $rawPage) ? max(1, min($totalPages, (int) $rawPage)) : 1;

        return $this->render('admin/agencies/index.html.twig', [
            'agencies' => $repo->findForAdmin($query, $status, $page, $perPage),
            'query' => $query,
            'selectedStatus' => $status?->value ?? '',
            'statusOptions' => AgencyStatus::cases(),
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/nova', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, Slugger $slugger): Response
    {
        $agency = new Agency();
        $form = $this->createForm(AgencyType::class, $agency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $agency->setSlug($slugger->slugify($agency->getSlug() ?: $agency->getName()));
            $admin = $this->getUser();
            if (!$admin instanceof User) {
                throw $this->createAccessDeniedException();
            }
            $em->persist($agency);
            $em->flush();
            $em->persist(new AgencyStatusHistory($agency, $admin, 'new', AgencyStatus::PENDING->value, 'Cadastro aguardando análise administrativa.'));
            $em->flush();
            $this->addFlash('success', 'Imobiliária cadastrada e enviada para análise. Ela não aparecerá no catálogo nem poderá acessar o painel até ser aprovada.');

            return $this->redirectToRoute('admin_agencies_index', ['status' => AgencyStatus::PENDING->value]);
        }

        return $this->render('admin/agencies/form.html.twig', ['form' => $form, 'heading' => 'Cadastrar imobiliária']);
    }

    #[Route('/{id<\d+>}/editar', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Agency $agency, Request $request, EntityManagerInterface $em, Slugger $slugger): Response
    {
        $form = $this->createForm(AgencyType::class, $agency);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $agency->setSlug($slugger->slugify($agency->getSlug()));
            $em->flush();
            $this->addFlash('success', 'Imobiliária atualizada.');

            return $this->redirectToRoute('admin_agencies_index');
        }

        return $this->render('admin/agencies/form.html.twig', ['form' => $form, 'heading' => 'Editar imobiliária']);
    }

    #[Route('/{id<\d+>}/aprovar', name: 'approve', methods: ['POST'])]
    public function approve(Agency $agency, Request $request, AgencyStatusManager $manager, UserAuditLogger $audit, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('approve-agency-' . $agency->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }
        $admin = $this->requireAdminUser();
        try {
            $manager->approve($agency, $admin);
            $audit->record($admin, null, $agency, 'agency_approved', ['new_status' => 'active']);
            $em->flush();
            $this->addFlash('success', 'Imobiliária aprovada e ativada.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('admin_agencies_index');
    }

    #[Route('/{id<\d+>}/rejeitar', name: 'reject', methods: ['POST'])]
    public function reject(Agency $agency, Request $request, AgencyStatusManager $manager, UserAuditLogger $audit, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('reject-agency-' . $agency->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }
        $reason = $request->request->get('reason');
        if (!is_string($reason) || trim($reason) === '' || mb_strlen(trim($reason)) > 1000) {
            $this->addFlash('error', 'Informe um motivo de rejeição de até 1000 caracteres.');
            return $this->redirectToRoute('admin_agencies_index', ['status' => AgencyStatus::PENDING->value]);
        }
        try {
            $admin = $this->requireAdminUser();
            $manager->reject($agency, $admin, trim($reason));
            $audit->record($admin, null, $agency, 'agency_rejected', ['new_status' => 'rejected', 'reason_code' => 'admin_rejection']);
            $em->flush();
            $this->addFlash('success', 'Imobiliária rejeitada. O motivo foi registrado no histórico.');
        } catch (\DomainException|\InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('admin_agencies_index');
    }

    #[Route('/{id<\d+>}/bloquear', name: 'block', methods: ['POST'])]
    public function block(Agency $agency, Request $request, AgencyStatusManager $manager, UserAuditLogger $audit, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('block-agency-' . $agency->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }
        $reason = $request->request->get('reason');
        if (!is_string($reason) || trim($reason) === '' || mb_strlen(trim($reason)) > 1000) {
            $this->addFlash('error', 'Informe um motivo para o bloqueio (até 1000 caracteres).');
            return $this->redirectToRoute('admin_agencies_index');
        }
        try {
            $admin = $this->requireAdminUser();
            $manager->block($agency, $admin, trim($reason));
            $audit->record($admin, null, $agency, 'agency_blocked', ['new_status' => 'blocked', 'reason_code' => 'admin_block']);
            $em->flush();
            $this->addFlash('success', 'Imobiliária bloqueada. Os anúncios foram removidos do catálogo e o acesso ao painel foi revogado.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('admin_agencies_index');
    }

    #[Route('/{id<\d+>}/reativar', name: 'activate', methods: ['POST'])]
    public function activate(Agency $agency, Request $request, AgencyStatusManager $manager, UserAuditLogger $audit, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('activate-agency-' . $agency->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }
        try {
            $admin = $this->requireAdminUser();
            $manager->activate($agency, $admin);
            $audit->record($admin, null, $agency, 'agency_activated', ['new_status' => 'active']);
            $em->flush();
            $this->addFlash('success', 'Imobiliária reativada.');
        } catch (\DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('admin_agencies_index');
    }

    private function requireAdminUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isGlobalAdmin()) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }
}
