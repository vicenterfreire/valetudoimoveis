<?php
declare(strict_types=1);
namespace App\Entity;
use App\Enum\AgencyStatus;
use App\Repository\AgencyRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: AgencyRepository::class)]
#[ORM\Table(name:'agencies')]
class Agency {
 #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'bigint')] private ?int $id=null;
 #[ORM\Column(length:150)] private string $name='';
 #[ORM\Column(length:180, unique:true)] private string $slug='';
 #[ORM\Column(length:20, nullable:true)] private ?string $document=null;
 #[ORM\Column(length:180, nullable:true)] private ?string $email=null;
 #[ORM\Column(length:30, nullable:true)] private ?string $phone=null;
 #[ORM\Column(length:30, nullable:true)] private ?string $whatsapp=null;
 #[ORM\Column(length:255, nullable:true)] private ?string $website=null;
 #[ORM\Column(type:'text', nullable:true)] private ?string $description=null;
 #[ORM\Column(length:255, nullable:true)] private ?string $logoPath=null;
 #[ORM\Column(length:100)] private string $city='';
 #[ORM\Column(length:2)] private string $state='SP';
 #[ORM\Column(length:20, enumType:AgencyStatus::class)] private AgencyStatus $status=AgencyStatus::PENDING;
 #[ORM\Column(type:'datetime_immutable', nullable:true)] private ?\DateTimeImmutable $blockedAt=null;
 #[ORM\Column(type:'datetime_immutable', nullable:true)] private ?\DateTimeImmutable $statusChangedAt=null;
 #[ORM\Column(type:'text', nullable:true)] private ?string $statusReason=null;
 #[ORM\Column(type:'text', nullable:true)] private ?string $blockedReason=null;
 #[ORM\Column(type:'datetime_immutable')] private \DateTimeImmutable $createdAt;
 #[ORM\Column(type:'datetime_immutable')] private \DateTimeImmutable $updatedAt;
 #[ORM\OneToMany(mappedBy:'agency', targetEntity:User::class)] private $users;
 #[ORM\OneToMany(mappedBy:'agency', targetEntity:Property::class)] private $properties;
 public function __construct(){ $this->createdAt=$this->updatedAt=new \DateTimeImmutable(); $this->users=new \Doctrine\Common\Collections\ArrayCollection(); $this->properties=new \Doctrine\Common\Collections\ArrayCollection(); }
 public function getId():?int{return $this->id;}
 public function getName():string{return $this->name;} public function setName(string $v):self{$this->name=trim($v);$this->touch();return $this;}
 public function getSlug():string{return $this->slug;} public function setSlug(string $v):self{$this->slug=trim($v);$this->touch();return $this;}
 public function getDocument():?string{return $this->document;} public function setDocument(?string $v):self{$this->document=$v?trim($v):null;return $this;}
 public function getEmail():?string{return $this->email;} public function setEmail(?string $v):self{$this->email=$v?trim($v):null;return $this;}
 public function getPhone():?string{return $this->phone;} public function setPhone(?string $v):self{$this->phone=$v?trim($v):null;return $this;}
 public function getWhatsapp():?string{return $this->whatsapp;} public function setWhatsapp(?string $v):self{$this->whatsapp=$v?trim($v):null;return $this;}
 public function getWebsite():?string{return $this->website;} public function setWebsite(?string $v):self{$this->website=$v?trim($v):null;return $this;}
 public function getDescription():?string{return $this->description;} public function setDescription(?string $v):self{$this->description=$v;return $this;}
 public function getLogoPath():?string{return $this->logoPath;} public function setLogoPath(?string $v):self{$this->logoPath=$v;$this->touch();return $this;}
 public function getCity():string{return $this->city;} public function setCity(string $v):self{$this->city=trim($v);return $this;}
 public function getState():string{return $this->state;} public function setState(string $v):self{$this->state=strtoupper(trim($v));return $this;}
 public function getStatus():AgencyStatus{return $this->status;} public function isActive():bool{return $this->status===AgencyStatus::ACTIVE;}
 public function getBlockedAt():?\DateTimeImmutable{return $this->blockedAt;} public function getBlockedReason():?string{return $this->blockedReason;}
 public function getStatusChangedAt():?\DateTimeImmutable{return $this->statusChangedAt;} public function getStatusReason():?string{return $this->statusReason;}
 public function getCreatedAt():\DateTimeImmutable{return $this->createdAt;} public function getUpdatedAt():\DateTimeImmutable{return $this->updatedAt;}
 public function getUsers(){return $this->users;} public function getProperties(){return $this->properties;}
 public function block(?string $reason):self{$this->status=AgencyStatus::BLOCKED;$this->blockedAt=new \DateTimeImmutable();$this->blockedReason=$reason;$this->statusChangedAt=$this->blockedAt;$this->statusReason=$reason;$this->touch();return $this;}
 public function approve():self{if(!in_array($this->status,[AgencyStatus::PENDING,AgencyStatus::REJECTED],true))throw new \DomainException('Somente imobiliárias pendentes ou rejeitadas podem ser aprovadas.');$this->status=AgencyStatus::ACTIVE;$this->blockedAt=null;$this->blockedReason=null;$this->statusChangedAt=new \DateTimeImmutable();$this->statusReason=null;$this->touch();return $this;}
 public function reject(string $reason):self{if($this->status!==AgencyStatus::PENDING)throw new \DomainException('Somente imobiliárias pendentes podem ser rejeitadas.');$reason=trim($reason);if($reason===''||mb_strlen($reason)>1000)throw new \InvalidArgumentException('Informe um motivo de rejeição de até 1000 caracteres.');$this->status=AgencyStatus::REJECTED;$this->statusChangedAt=new \DateTimeImmutable();$this->statusReason=$reason;$this->touch();return $this;}
 public function activate():self{if($this->status!==AgencyStatus::BLOCKED)throw new \DomainException('Somente imobiliárias bloqueadas podem ser reativadas.');$this->status=AgencyStatus::ACTIVE;$this->blockedAt=null;$this->blockedReason=null;$this->statusChangedAt=new \DateTimeImmutable();$this->statusReason=null;$this->touch();return $this;}
 private function touch():void{$this->updatedAt=new \DateTimeImmutable();}
}
