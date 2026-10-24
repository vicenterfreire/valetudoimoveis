<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Agency;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserAuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/imobiliarias/{id<\d+>}/gestor', name: 'admin_agency_manager_')]
#[IsGranted('ROLE_ADMIN')]
final class AgencyManagerAdminController extends AbstractController
{
    #[Route('/novo', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Agency $agency,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserAuditLogger $audit,
    ): Response {
        foreach ($agency->getUsers() as $existingUser) {
            if ($existingUser instanceof User && in_array('ROLE_AGENCY_MANAGER', $existingUser->getRoles(), true)) {
                $this->addFlash('error', 'Esta imobiliária já possui um gestor. Use o painel de usuários para administrar a conta existente.');
                return $this->redirectToRoute('admin_agencies_index');
            }
        }

        $fullName = '';
        $email = '';
        $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create-agency-manager-' . $agency->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token de segurança inválido.');
            }

            $rawName = $request->request->get('full_name');
            $rawEmail = $request->request->get('email');
            $rawPassword = $request->request->get('password');
            $rawPasswordConfirmation = $request->request->get('password_confirmation');
            $fullName = is_string($rawName) ? trim($rawName) : '';
            $email = is_string($rawEmail) ? mb_strtolower(trim($rawEmail)) : '';
            $password = is_string($rawPassword) ? $rawPassword : '';
            $passwordConfirmation = is_string($rawPasswordConfirmation) ? $rawPasswordConfirmation : '';
            if ($fullName === '' || mb_strlen($fullName) > 150) {
                $errors[] = 'Informe o nome completo, com no máximo 150 caracteres.';
            }
            if (mb_strlen($password) < 12 || mb_strlen($password) > 4096) {
                $errors[] = 'A senha deve ter entre 12 e 4096 caracteres.';
            } elseif (!hash_equals($password, $passwordConfirmation)) {
                $errors[] = 'A confirmação da senha não corresponde à senha informada.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
                $errors[] = 'Informe um endereço de e-mail válido, com no máximo 180 caracteres.';
            } elseif ($users->findOneBy(['email' => $email]) instanceof User) {
                $errors[] = 'Já existe uma conta cadastrada com este e-mail.';
            }

            if ($errors === []) {
                $manager = new User();
                $manager->setFullName($fullName)
                    ->setEmail($email)
                    ->setAgency($agency)
                    ->setRoles(['ROLE_AGENCY_MANAGER'])
                    ->setIsActive(true);
                $manager->setPassword($hasher->hashPassword($manager, $password));
                $em->persist($manager);
                $admin = $this->getUser();
                if (!$admin instanceof User || !$admin->isGlobalAdmin()) {
                    throw $this->createAccessDeniedException();
                }
                $audit->record($admin, $manager, $agency, 'agency_manager_created');
                $em->flush();

                $this->addFlash('success', 'Primeiro gestor cadastrado. Compartilhe o e-mail e a senha inicial diretamente com a pessoa por um canal seguro. A conta só poderá entrar no painel quando a imobiliária for aprovada.');

                return $this->redirectToRoute('admin_agencies_index', ['status' => $agency->getStatus()->value]);
            }
        }

        return $this->render('admin/agencies/manager_form.html.twig', [
            'agency' => $agency,
            'fullName' => $fullName,
            'email' => $email,
            'errors' => $errors,
        ]);
    }
}
