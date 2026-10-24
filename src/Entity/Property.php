<?php
declare(strict_types=1);
namespace App\Entity;
use App\Enum\ListingType;
use App\Enum\PropertyStatus;
use App\Enum\PropertyModerationStatus;
use App\Enum\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass:PropertyRepository::class)]
#[ORM\Table(name:'properties', indexes:[new ORM\Index(name:'idx_property_agency_status',columns:['agency_id','status']),new ORM\Index(name:'idx_property_location',columns:['city','state'])])]
class Property {
 #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:'bigint')] private ?int $id=null;
 #[ORM\ManyToOne(targetEntity:Agency::class,inversedBy:'properties')] #[ORM\JoinColumn(nullable:false,onDelete:'RESTRICT')] private Agency $agency;
 #[ORM\Column(length:180)] private string $title='';
 #[ORM\Column(length:220)] private string $slug='';
 #[ORM\Column(type:'text')] private string $description='';
 #[ORM\Column(length:30,enumType:PropertyType::class)] private PropertyType $propertyType=PropertyType::OTHER;
 #[ORM\Column(length:20,enumType:ListingType::class)] private ListingType $listingType=ListingType::SALE;
 #[ORM\Column(type:'decimal',precision:12,scale:2)] private string $price='0.00';
 #[ORM\Column(type:'decimal',precision:12,scale:2,nullable:true)] private ?string $condoFee=null;
 #[ORM\Column(type:'decimal',precision:12,scale:2,nullable:true)] private ?string $iptuAmount=null;
 #[ORM\Column(type:'decimal',precision:10,scale:2,nullable:true)] private ?string $areaM2=null;
 #[ORM\Column(type:'smallint',nullable:true)] private ?int $bedrooms=null;
 #[ORM\Column(type:'smallint',nullable:true)] private ?int $bathrooms=null;
 #[ORM\Column(type:'smallint',nullable:true)] private ?int $parkingSpaces=null;
 #[ORM\Column(length:100)] private string $city='';
 #[ORM\Column(length:2)] private string $state='SP';
 #[ORM\Column(length:100,nullable:true)] private ?string $neighborhood=null;
 #[ORM\Column(length:180,nullable:true)] private ?string $street=null;
 #[ORM\Column(length:20,nullable:true)] private ?string $streetNumber=null;
 #[ORM\Column(length:10,nullable:true)] private ?string $zipCode=null;
 #[ORM\Column(length:20,enumType:PropertyStatus::class)] private PropertyStatus $status=PropertyStatus::DRAFT;
 #[ORM\Column(length:30,enumType:PropertyModerationStatus::class)] private PropertyModerationStatus $moderationStatus=PropertyModerationStatus::PENDING;
 #[ORM\Column(type:'datetime_immutable',nullable:true)] private ?\DateTimeImmutable $publishedAt=null;
 #[ORM\Column(type:'datetime_immutable')] private \DateTimeImmutable $createdAt;
 #[ORM\Column(type:'datetime_immutable')] private \DateTimeImmutable $updatedAt;
 #[ORM\OneToMany(mappedBy:'property',targetEntity:PropertyImage::class,cascade:['persist'],orphanRemoval:true)] #[ORM\OrderBy(['sortOrder'=>'ASC', 'id'=>'ASC'])] private $images;
 public function __construct(){ $this->createdAt=$this->updatedAt=new \DateTimeImmutable();$this->images=new \Doctrine\Common\Collections\ArrayCollection(); }
 public function getId():?int{return $this->id;} public function getAgency():Agency{return $this->agency;} public function setAgency(Agency $v):self{$this->agency=$v;return $this;}
 public function getTitle():string{return $this->title;} public function setTitle(string $v):self{$this->title=trim($v);return $this;} public function getSlug():string{return $this->slug;} public function setSlug(string $v):self{$this->slug=trim($v);return $this;}
 public function getDescription():string{return $this->description;} public function setDescription(string $v):self{$this->description=trim($v);return $this;}
 public function getPropertyType():PropertyType{return $this->propertyType;} public function setPropertyType(PropertyType $v):self{$this->propertyType=$v;return $this;}
 public function getListingType():ListingType{return $this->listingType;} public function setListingType(ListingType $v):self{$this->listingType=$v;return $this;}
 public function getPrice():string{return $this->price;} public function setPrice(string|int|float $v):self{$this->price=number_format((float)$v,2,'.','');return $this;}
 public function getCondoFee():?string{return $this->condoFee;} public function setCondoFee(string|int|float|null $v):self{$this->condoFee=$v===null||$v===''?null:number_format((float)$v,2,'.','');return $this;}
 public function getIptuAmount():?string{return $this->iptuAmount;} public function setIptuAmount(string|int|float|null $v):self{$this->iptuAmount=$v===null||$v===''?null:number_format((float)$v,2,'.','');return $this;}
 public function getAreaM2():?string{return $this->areaM2;} public function setAreaM2(string|int|float|null $v):self{$this->areaM2=$v===null||$v===''?null:number_format((float)$v,2,'.','');return $this;}
 public function getBedrooms():?int{return $this->bedrooms;} public function setBedrooms(?int $v):self{$this->bedrooms=$v;return $this;} public function getBathrooms():?int{return $this->bathrooms;} public function setBathrooms(?int $v):self{$this->bathrooms=$v;return $this;} public function getParkingSpaces():?int{return $this->parkingSpaces;} public function setParkingSpaces(?int $v):self{$this->parkingSpaces=$v;return $this;}
 public function getCity():string{return $this->city;} public function setCity(string $v):self{$this->city=trim($v);return $this;} public function getState():string{return $this->state;} public function setState(string $v):self{$this->state=strtoupper(trim($v));return $this;}
 public function getNeighborhood():?string{return $this->neighborhood;} public function setNeighborhood(?string $v):self{$this->neighborhood=$v?trim($v):null;return $this;} public function getStreet():?string{return $this->street;} public function setStreet(?string $v):self{$this->street=$v?trim($v):null;return $this;} public function getStreetNumber():?string{return $this->streetNumber;} public function setStreetNumber(?string $v):self{$this->streetNumber=$v?trim($v):null;return $this;} public function getZipCode():?string{return $this->zipCode;} public function setZipCode(?string $v):self{$this->zipCode=$v?trim($v):null;return $this;}
 public function getStatus():PropertyStatus{return $this->status;} public function getModerationStatus():PropertyModerationStatus{return $this->moderationStatus;} public function setModerationStatus(PropertyModerationStatus $v):self{$this->moderationStatus=$v;return $this;} public function getPublishedAt():?\DateTimeImmutable{return $this->publishedAt;} public function getCreatedAt():\DateTimeImmutable{return $this->createdAt;} public function getUpdatedAt():\DateTimeImmutable{return $this->updatedAt;} public function touch():self{$this->updatedAt=new \DateTimeImmutable();return $this;} public function getImages():\Doctrine\Common\Collections\Collection{return $this->images;} public function addImage(PropertyImage $image):self{if(!$this->images->contains($image)){$this->images->add($image);$image->setProperty($this);}return $this;} public function removeImage(PropertyImage $image):self{if($this->images->removeElement($image)&&$image->getProperty()===$this){$image->setProperty(null);}return $this;}
 public function setStatus(PropertyStatus $v):self{$this->status=$v;if($v===PropertyStatus::PUBLISHED)$this->publishedAt??=new \DateTimeImmutable();return $this;}
 public function isPubliclyVisible():bool{return $this->status===PropertyStatus::PUBLISHED && $this->moderationStatus===PropertyModerationStatus::APPROVED && $this->agency->isActive();}
}
