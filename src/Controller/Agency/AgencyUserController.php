<?php
declare(strict_types=1);

namespace App\Controller\Agency;

use App\Entity\User;
use App\Form\AgencyUserType;
use App\Repository\UserRepository;
use App\Security\AgencyAccess;
use App\Service\AccountActionService;
use App\Service\UserAuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/painel/equipe', name: 'agency_users_')]
#[IsGranted('ROLE_AGENCY_MANAGER')]
class AgencyUserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(AgencyAccess $access, UserRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('agency/users/index.html.twig', [
            'members' => $repository->findAgencyUsers($access->requireManager($user)),
        ]);
    }

    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        AgencyAccess $access,
        EntityManagerInterface $em,
        UserRepository $repository,
        UserPasswordHasherInterface $hasher,
        UserAuditLogger $audit,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $agency = $access->requireManager($user);
        $member = (new User())->setAgency($agency)->setRoles(['ROLE_AGENCY_STAFF']);
        $form = $this->createForm(AgencyUserType::class, $member, ['password_required' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim($member->getEmail()));
            if ($repository->findOneBy(['email' => $email])) {
                $form->get('email')->addError(new FormError('Este e-mail já está cadastrado.'));
            } else {
                $member->setPassword($hasher->hashPassword($member, (string) $form->get('plainPassword')->getData()));
                $em->persist($member);
                $audit->record($user, $member, $agency, 'staff_created', ['changed_fields' => ['fullName', 'email']]);
                $em->flush();
                $this->addFlash('success', 'Funcionário cadastrado.');

                return $this->redirectToRoute('agency_users_index');
            }
        }

        return $this->render('agency/users/form.html.twig', ['form' => $form, 'heading' => 'Adicionar funcionário', 'member' => $member]);
    }

    #[Route('/{id<\d+>}/editar', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        User $member,
        Request $request,
        AgencyAccess $access,
        EntityManagerInterface $em,
        UserRepository $repository,
        UserPasswordHasherInterface $hasher,
        AccountActionService $accountActions,
        UserAuditLogger $audit,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $agency = $access->requireManager($user);
        $this->assertManagedStaff($user, $member, $agency->getId());
        $originalEmail = $member->getEmail();

        $form = $this->createForm(AgencyUserType::class, $member, ['password_required' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestedEmail = mb_strtolower(trim($member->getEmail()));
            $member->setEmail($originalEmail);
            $duplicate = $repository->findOneBy(['email' => $requestedEmail]);
            if ($duplicate && $duplicate->getId() !== $member->getId()) {
                $form->get('email')->addError(new FormError('Este e-mail já está cadastrado.'));
            } else {
                $changedFields = [];
                $newPassword = $form->get('plainPassword')->getData();
                if (is_string($newPassword) && $newPassword !== '') {
                    $member->setPassword($hasher->hashPassword($member, $newPassword));
                    $changedFields[] = 'password';
                }
                $changedFields[] = 'fullName';
                $audit->record($user, $member, $agency, 'staff_updated', ['changed_fields' => $changedFields]);
                $em->flush();

                if ($requestedEmail !== $originalEmail) {
                    try {
                        $accountActions->requestEmailChange($member, $requestedEmail);
                        $audit->record($user, $member, $agency, 'email_change_requested', ['changed_fields' => ['email']]);
                        $em->flush();
                        $this->addFlash('success', 'Dados salvos. Enviamos um link ao novo endereço para confirmar a alteração do e-mail.');
                    } catch (TransportExceptionInterface|\RuntimeException $exception) {
                        $this->addFlash('error', 'Os dados foram salvos, mas não foi possível enviar a confirmação de e-mail. Verifique a configuração de envio e tente novamente.');
                    }
                } else {
                    $this->addFlash('success', 'Dados do funcionário atualizados.');
                }

                return $this->redirectToRoute('agency_users_index');
            }
        }

        return $this->render('agency/users/form.html.twig', ['form' => $form, 'heading' => 'Editar funcionário', 'member' => $member]);
    }

    #[Route('/{id<\d+>}/status', name: 'status', methods: ['POST'])]
    public function status(
        User $member,
        Request $request,
        AgencyAccess $access,
        EntityManagerInterface $em,
        UserAuditLogger $audit,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $agency = $access->requireManager($user);
        $this->assertManagedStaff($user, $member, $agency->getId());

        if (!$this->isCsrfTokenValid('agency-user-status-' . $member->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de segurança inválido.');
        }

        $oldStatus = $member->isActive() ? 'active' : 'inactive';
        $member->setIsActive(!$member->isActive());
        $audit->record($user, $member, $agency, 'staff_status_changed', [
            'old_status' => $oldStatus,
            'new_status' => $member->isActive() ? 'active' : 'inactive',
        ]);
        $em->flush();
        $this->addFlash('success', $member->isActive() ? 'Funcionário reativado.' : 'Funcionário desativado.');

        return $this->redirectToRoute('agency_users_index');
    }

    private function assertManagedStaff(User $manager, User $member, ?int $agencyId): void
    {
        if (
            $member->getId() === $manager->getId()
            || $member->isAgencyManager()
            || $member->getAgency()?->getId() !== $agencyId
            || $member->isGlobalAdmin()
        ) {
            throw $this->createAccessDeniedException('Você só pode gerenciar funcionários da sua própria imobiliária.');
        }
    }
}
