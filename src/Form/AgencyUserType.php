<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AgencyUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nome completo',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 150)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['password_required'] ? 'Senha inicial' : 'Nova senha (opcional)',
                'mapped' => false,
                'required' => $options['password_required'],
                'help' => $options['password_required'] ? 'Mínimo de 12 caracteres.' : 'Deixe em branco para manter a senha atual.',
                'constraints' => $options['password_required']
                    ? [new Assert\NotBlank(), new Assert\Length(min: 12, max: 4096)]
                    : [new Assert\Length(min: 12, max: 4096, allowEmptyString: true)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_required' => true,
        ]);
        $resolver->setAllowedTypes('password_required', 'bool');
    }
}
