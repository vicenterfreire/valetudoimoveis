<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Entity\User;
use App\Repository\AgencyRepository;
use App\Repository\UserRepository;
use App\Service\UserAuditLogger;
use App\Service\UserManagementPolicy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/usuarios', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
final class UserAdminController extends AbstractController
{
    private const ROLES = ['ROLE_ADMIN', 'ROLE_AGENCY_MANAGER', 'ROLE_AGENCY_STAFF'];

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $users, AgencyRepository $agencies): Response
    {
        $term = $request->query->get('q', '');
        $term = is_string($term) ? mb_substr(trim($term), 0, 100) : '';
        $activeRaw = $request->query->get('active', '');
        $active = $activeRaw === '1' ? true : ($activeRaw === '0' ? false : null);
        $roleRaw = $request->query->get('role', '');
        $role = is_string($roleRaw) && in_array($roleRaw, self::ROLES, true) ? $roleRaw : null;
        $agencyRaw = $request->query->get('agency', '');
        $agencyId = is_scalar($agencyRaw) && ctype_digit((string) $agencyRaw) && (int) $agencyRaw > 0 ? (int) $agencyRaw : null;

        $perPage = 25;
        $totalItems = $users->countForAdmin($term, $active, $role, $agencyId);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $rawPage = $request->query->get('page', '1');
        $page = is_scalar($rawPage) && ctype_digit((string) $rawPage) ? max(1, min($totalPages, (int) $rawPage)) : 1;

        return $this->render('admin/users/index.html.twig', [
            'users' => $users->findForAdmin($term, $active, $role, $agencyId, $page, $perPage),
            'agencies' => $agencies->findBy([], ['name' => 'ASC']),
            'query' => $term,
            'selectedActive' => $activeRaw,
            'selectedRole' => $role ?? '',
            'selectedAgency' => $agencyId ?? '',
            'roles' => self::ROLES,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/{id<\d+>}/status', name: 'status', methods: ['POST'])]
    public function status(User $target, Request $request, EntityManagerInterface $em, UserAuditLogger $audit, UserManagementPolicy $policy): Response
    {
        $this->validateCsrf($request, 'admin-user-status-' . $target->getId());
        $actor = $this->requireAdmin();
        if (!$policy->canManageTarget($actor, $target)) {
            $this->addFlash('error', 'Por segurança, não é possível alterar o próprio status por este painel.');
            return $this->redirectToRoute('admin_users_index');
        }
        if ($policy->wouldRemoveLastActiveAdmin($target, null, !$target->isActive())) {
            $this->addFlash('error', 'Não é possível desativar o último administrador ativo.');
            return $this->redirectToRoute('admin_users_index');
        }

        $oldStatus = $target->isActive() ? 'active' : 'inactive';
        $target->setIsActive(!$target->isActive());
        $audit->record($actor, $target, $target->getAgency(), 'global_user_status_changed', [
            'old_status' => $oldStatus,
            'new_status' => $target->isActive() ? 'active' : 'inactive',
        ]);
        $em->flush();
        $this->addFlash('success', $target->isActive() ? 'Usuário ativado.' : 'Usuário desativado.');
        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id<\d+>}/permissoes', name: 'permissions', methods: ['POST'])]
    public function permissions(User $target, Request $request, AgencyRepository $agencies, EntityManagerInterface $em, UserAuditLogger $audit, UserManagementPolicy $policy): Response
    {
        $this->validateCsrf($request, 'admin-user-permissions-' . $target->getId());
        $actor = $this->requireAdmin();
        if (!$policy->canManageTarget($actor, $target)) {
            $this->addFlash('error', 'Por segurança, não é possível alterar as próprias permissões por este painel.');
            return $this->redirectToRoute('admin_users_index');
        }

        $role = $request->request->get('role');
        if (!is_string($role) || !in_array($role, self::ROLES, true)) {
            $this->addFlash('error', 'Papel informado inválido.');
            return $this->redirectToRoute('admin_users_index');
        }
        $agencyRaw = $request->request->get('agency');
        $agencyId = is_string($agencyRaw) && ctype_digit($agencyRaw) && (int) $agencyRaw > 0 ? (int) $agencyRaw : null;
        $agency = $agencyId !== null ? $agencies->find($agencyId) : null;
        if ($agencyId !== null && !$agency instanceof Agency) {
            $this->addFlash('error', 'Imobiliária selecionada não encontrada.');
            return $this->redirectToRoute('admin_users_index');
        }
        if ($role !== 'ROLE_ADMIN' && !$agency instanceof Agency) {
            $this->addFlash('error', 'Selecione uma imobiliária para gestores e funcionários.');
            return $this->redirectToRoute('admin_users_index');
        }
        if ($role === 'ROLE_ADMIN') {
            $agency = null;
        }
        if ($policy->wouldRemoveLastActiveAdmin($target, $role, null)) {
            $this->addFlash('error', 'Não é possível remover o papel do último administrador ativo.');
            return $this->redirectToRoute('admin_users_index');
        }

        $oldRoles = $target->getRoles();
        $oldAgencyId = $target->getAgency()?->getId();
        $target->setRoles([$role])->setAgency($agency);
        $audit->record($actor, $target, $agency, 'global_user_permissions_changed', [
            'old_roles' => $oldRoles,
            'new_roles' => $target->getRoles(),
            'old_agency_id' => $oldAgencyId,
            'new_agency_id' => $agency?->getId(),
        ]);
        $em->flush();
        $this->addFlash('success', 'Papel e associação do usuário atualizados.');
        return $this->redirectToRoute('admin_users_index');
    }

    private function requireAdmin(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->isGlobalAdmin()) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }

    private function validateCsrf(Request $request, string $tokenId): void
    {
        $rawToken = $request->request->get('_token');
        $token = is_string($rawToken) ? $rawToken : '';
        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }
    }
}
