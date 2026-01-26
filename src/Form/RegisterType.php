<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Correo electrónico',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Contraseña',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('registrarse', SubmitType::class, [
                'label' => 'Registrarse',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true, // Activar protección CSRF
            'csrf_field_name' => '_csrf_token', // Name del campo oculto
            'csrf_token_id'   => 'register', // Identificador único del token
        ]);
    }
}