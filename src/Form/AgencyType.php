<?php
declare(strict_types=1);
namespace App\Form;
use App\Entity\Agency; use Symfony\Component\Form\AbstractType; use Symfony\Component\Form\FormBuilderInterface; use Symfony\Component\OptionsResolver\OptionsResolver; use Symfony\Component\Form\Extension\Core\Type\{TextType,EmailType,TextareaType,UrlType}; use Symfony\Component\Validator\Constraints as Assert;
class AgencyType extends AbstractType {
 public function buildForm(FormBuilderInterface $b,array $o):void{$b->add('name',TextType::class,['label'=>'Nome','constraints'=>[new Assert\NotBlank(),new Assert\Length(max:150)]])->add('slug',TextType::class,['label'=>'Slug','constraints'=>[new Assert\NotBlank()]])->add('document',TextType::class,['label'=>'CNPJ','required'=>false])->add('email',EmailType::class,['label'=>'E-mail','required'=>false])->add('phone',TextType::class,['label'=>'Telefone','required'=>false])->add('whatsapp',TextType::class,['label'=>'WhatsApp','required'=>false])->add('website',UrlType::class,['label'=>'Site','required'=>false])->add('description',TextareaType::class,['label'=>'Descrição','required'=>false])->add('city',TextType::class,['label'=>'Cidade','constraints'=>[new Assert\NotBlank()]])->add('state',TextType::class,['label'=>'UF','constraints'=>[new Assert\NotBlank(),new Assert\Length(exactly:2)]]);}
 public function configureOptions(OptionsResolver $r):void{$r->setDefaults(['data_class'=>Agency::class]);}
}
