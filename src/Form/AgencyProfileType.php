<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Agency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class AgencyProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome público da imobiliária',
                'constraints' => [new Assert\NotBlank(message: 'Informe o nome da imobiliária.'), new Assert\Length(max: 150)],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Sobre a imobiliária',
                'required' => false,
                'attr' => ['maxlength' => 3000, 'rows' => 6, 'placeholder' => 'Conte um pouco sobre a equipe, a região e o atendimento.'],
                'constraints' => [new Assert\Length(max: 3000)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail público',
                'required' => false,
                'constraints' => [new Assert\Email(mode: Assert\Email::VALIDATION_MODE_HTML5), new Assert\Length(max: 180)],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefone público',
                'required' => false,
                'constraints' => [new Assert\Length(max: 30)],
            ])
            ->add('whatsapp', TextType::class, [
                'label' => 'WhatsApp para contatos',
                'required' => false,
                'constraints' => [new Assert\Length(max: 30)],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site',
                'required' => false,
                'default_protocol' => 'https',
                'constraints' => [new Assert\Url(protocols: ['http', 'https']), new Assert\Length(max: 255)],
            ])
            ->add('city', TextType::class, [
                'label' => 'Cidade',
                'constraints' => [new Assert\NotBlank(message: 'Informe a cidade.'), new Assert\Length(max: 100)],
            ])
            ->add('state', TextType::class, [
                'label' => 'UF',
                'attr' => ['maxlength' => 2, 'autocomplete' => 'address-level1'],
                'constraints' => [new Assert\NotBlank(message: 'Informe a UF.'), new Assert\Length(exactly: 2)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Agency::class]);
    }
}
