<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use App\Form\Constraints\PasswordConstraints;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Contraseña actual',
                'mapped' => false,
                'attr' => ['autocomplete' => 'current-password'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Introduce tu contraseña actual.'),
                    new UserPassword(message: 'La contraseña actual no es correcta.'),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Nueva contraseña',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Repite la nueva contraseña',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Las nuevas contraseñas no coinciden.',
                'constraints' => PasswordConstraints::newPassword(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }
}
