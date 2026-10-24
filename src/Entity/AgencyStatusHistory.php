<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name:'agency_status_history', indexes:[new ORM\Index(name:'IDX_DEF55D06CDEADB2A',columns:['agency_id']),new ORM\Index(name:'IDX_DEF55D066352511C',columns:['admin_user_id'])])]
class AgencyStatusHistory {
 #[ORM\Id,ORM\GeneratedValue,ORM\Column(type:'bigint')] private ?int $id=null;
 #[ORM\ManyToOne(targetEntity:Agency::class)] #[ORM\JoinColumn(nullable:false,onDelete:'RESTRICT')] private Agency $agency;
 #[ORM\ManyToOne(targetEntity:User::class)] #[ORM\JoinColumn(nullable:true,onDelete:'SET NULL')] private ?User $adminUser;
 #[ORM\Column(length:20)] private string $previousStatus;
 #[ORM\Column(length:20)] private string $newStatus;
 #[ORM\Column(type:'text',nullable:true)] private ?string $reason;
 #[ORM\Column(type:'datetime_immutable')] private \DateTimeImmutable $createdAt;
 public function __construct(Agency $a,?User $u,string $old,string $new,?string $reason=null){$this->agency=$a;$this->adminUser=$u;$this->previousStatus=$old;$this->newStatus=$new;$this->reason=$reason;$this->createdAt=new \DateTimeImmutable();}
}
