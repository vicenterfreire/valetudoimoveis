<?php
declare(strict_types=1);
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; use Symfony\Component\HttpFoundation\Response; use Symfony\Component\Routing\Attribute\Route; use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
class SecurityController extends AbstractController {
 #[Route('/entrar',name:'app_login',methods:['GET','POST'])] public function login(AuthenticationUtils $a):Response{if($this->getUser())return $this->redirectToRoute('dashboard');return $this->render('security/login.html.twig',['last_username'=>$a->getLastUsername(),'error'=>$a->getLastAuthenticationError()]);}
 #[Route('/sair',name:'app_logout',methods:['POST'])] public function logout():never{throw new \LogicException('O firewall do Symfony deve interceptar esta rota.');}
}
