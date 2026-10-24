<?php
declare(strict_types=1);
namespace App\Form;
use App\Entity\Property; use App\Enum\ListingType; use App\Enum\PropertyType as Kind; use Symfony\Component\Form\AbstractType; use Symfony\Component\Form\FormBuilderInterface; use Symfony\Component\OptionsResolver\OptionsResolver; use Symfony\Component\Form\Extension\Core\Type\{TextType,TextareaType,ChoiceType,MoneyType,NumberType,IntegerType}; use Symfony\Component\Validator\Constraints as Assert;
class PropertyTypeForm extends AbstractType {
 public function buildForm(FormBuilderInterface $b,array $o):void{
  $b->add('title',TextType::class,['label'=>'Título','constraints'=>[new Assert\NotBlank(),new Assert\Length(max:180)]])
   ->add('slug',TextType::class,['label'=>'Slug personalizado (opcional)','required'=>false,'constraints'=>[new Assert\Length(max:220),new Assert\Regex(pattern:'/^[a-zA-Z0-9\p{L}\p{M}\s_-]*$/u',message:'Use apenas letras, números, espaços e hífens no slug.')]])
   ->add('description',TextareaType::class,['label'=>'Descrição','constraints'=>[new Assert\NotBlank(),new Assert\Length(max:20000)]])
   ->add('propertyType',ChoiceType::class,['label'=>'Tipo','choices'=>['Casa'=>Kind::HOUSE,'Apartamento'=>Kind::APARTMENT,'Terreno'=>Kind::LAND,'Comercial'=>Kind::COMMERCIAL,'Outro'=>Kind::OTHER]])
   ->add('listingType',ChoiceType::class,['label'=>'Finalidade','choices'=>['Venda'=>ListingType::SALE,'Aluguel'=>ListingType::RENT]])
   ->add('price',MoneyType::class,['label'=>'Preço','currency'=>'BRL','constraints'=>[new Assert\NotBlank(),new Assert\PositiveOrZero(),new Assert\LessThanOrEqual(9999999999.99)]])
   ->add('condoFee',MoneyType::class,['label'=>'Condomínio','currency'=>'BRL','required'=>false,'constraints'=>[new Assert\PositiveOrZero(),new Assert\LessThanOrEqual(9999999999.99)]])
   ->add('iptuAmount',MoneyType::class,['label'=>'IPTU','currency'=>'BRL','required'=>false,'constraints'=>[new Assert\PositiveOrZero(),new Assert\LessThanOrEqual(9999999999.99)]])
   ->add('areaM2',NumberType::class,['label'=>'Área (m²)','required'=>false,'scale'=>2,'constraints'=>[new Assert\PositiveOrZero(),new Assert\LessThanOrEqual(99999999.99)]])
   ->add('bedrooms',IntegerType::class,['label'=>'Quartos','required'=>false,'scale'=>0,'constraints'=>[new Assert\Range(min:0,max:99),new Assert\Type('integer')]])
   ->add('bathrooms',IntegerType::class,['label'=>'Banheiros','required'=>false,'scale'=>0,'constraints'=>[new Assert\Range(min:0,max:99),new Assert\Type('integer')]])
   ->add('parkingSpaces',IntegerType::class,['label'=>'Vagas','required'=>false,'scale'=>0,'constraints'=>[new Assert\Range(min:0,max:99),new Assert\Type('integer')]])
   ->add('city',TextType::class,['label'=>'Cidade','constraints'=>[new Assert\NotBlank(),new Assert\Length(max:100)]])
   ->add('state',TextType::class,['label'=>'UF','constraints'=>[new Assert\NotBlank(),new Assert\Length(exactly:2),new Assert\Regex(pattern:'/^[A-Za-z]{2}$/',message:'Informe a sigla de UF com duas letras.')]])
   ->add('neighborhood',TextType::class,['label'=>'Bairro','required'=>false,'constraints'=>[new Assert\Length(max:100)]])
   ->add('street',TextType::class,['label'=>'Rua (privada por padrão)','required'=>false,'constraints'=>[new Assert\Length(max:180)]])
   ->add('streetNumber',TextType::class,['label'=>'Número','required'=>false,'constraints'=>[new Assert\Length(max:20)]])
   ->add('zipCode',TextType::class,['label'=>'CEP','required'=>false,'constraints'=>[new Assert\Regex(pattern:'/^(?:[0-9]{5}-?[0-9]{3})?$/',message:'Informe um CEP válido com 8 dígitos.'),new Assert\Length(max:9)] ]);
 }
 public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['data_class'=>Property::class]);}
}
