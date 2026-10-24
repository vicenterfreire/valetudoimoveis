<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\User; use App\Security\AgencyAccess; use App\Repository\PropertyRepository; use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; use Symfony\Component\HttpFoundation\Response; use Symfony\Component\Routing\Attribute\Route;
class DashboardController extends AbstractController {
 #[Route('/painel',name:'dashboard',methods:['GET'])] public function index(AgencyAccess $access,PropertyRepository $repo):Response{$u=$this->getUser();if(!$u instanceof User)return $this->redirectToRoute('app_login');if($u->isGlobalAdmin())return $this->redirectToRoute('admin_agencies_index');$a=$access->requireActiveAgency($u);return $this->render('agency/dashboard.html.twig',['agency'=>$a,'properties'=>$repo->findForAgency($a)]);}
}
