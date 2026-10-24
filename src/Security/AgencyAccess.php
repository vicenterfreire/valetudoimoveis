<?php
declare(strict_types=1);
namespace App\Security;
use App\Entity\Agency; use App\Entity\Property; use App\Entity\User; use Symfony\Component\Security\Core\Exception\AccessDeniedException;
class AgencyAccess {
 public function requireActiveAgency(User $u):Agency{$a=$u->getAgency();if($u->isGlobalAdmin()||!$u->isActive()||!$a||!$a->isActive())throw new AccessDeniedException('Conta sem acesso ativo à imobiliária.');return $a;}
 public function assertCanManageProperty(User $u,Property $p):void{if($u->isGlobalAdmin())return;$a=$this->requireActiveAgency($u);if($p->getAgency()->getId()!==$a->getId())throw new AccessDeniedException('Acesso a imóvel de outra imobiliária negado.');}
 public function requireManager(User $u):Agency{$a=$this->requireActiveAgency($u);if(!$u->isAgencyManager())throw new AccessDeniedException('Apenas o responsável pode executar esta ação.');return $a;}
}
