<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Nombre',
            'attr' => [
                'class' => 'form-control',
                'autocomplete' => 'name',
            ],
            'constraints' => [
                new NotBlank(message: 'El nombre no puede estar vacío.'),
                new Length(
                    min: 2,
                    max: 100,
                    minMessage: 'El nombre debe tener al menos {{ limit }} caracteres.',
                    maxMessage: 'El nombre no puede superar {{ limit }} caracteres.',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'profile_edit',
        ]);
    }
}
