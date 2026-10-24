<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name:'property_images')]
class PropertyImage {
 #[ORM\Id,ORM\GeneratedValue,ORM\Column(type:'bigint')] private ?int $id=null;
 #[ORM\ManyToOne(targetEntity:Property::class,inversedBy:'images')] #[ORM\JoinColumn(nullable:false,onDelete:'CASCADE')] private ?Property $property=null;
 #[ORM\Column(length:255)] private string $filePath='';
 #[ORM\Column(length:255,nullable:true)] private ?string $thumbnailPath=null;
 #[ORM\Column(length:180,nullable:true)] private ?string $altText=null;
 #[ORM\Column(type:'smallint',options:['default'=>0])] private int $sortOrder=0;
 #[ORM\Column(type:'datetime_immutable')] private \DateTimeImmutable $createdAt;
 public function __construct(){ $this->createdAt=new \DateTimeImmutable(); }
 public function getId():?int{return $this->id;} public function getProperty():?Property{return $this->property;} public function setProperty(?Property $v):self{$this->property=$v;return $this;}
 public function getFilePath():string{return $this->filePath;} public function setFilePath(string $v):self{$this->filePath=$v;return $this;} public function getThumbnailPath():?string{return $this->thumbnailPath;} public function setThumbnailPath(?string $v):self{$this->thumbnailPath=$v;return $this;} public function getAltText():?string{return $this->altText;} public function setAltText(?string $v):self{$this->altText=$v;return $this;}
 public function getSortOrder():int{return $this->sortOrder;} public function setSortOrder(int $v):self{$this->sortOrder=$v;return $this;} public function getCreatedAt():\DateTimeImmutable{return $this->createdAt;}
}
