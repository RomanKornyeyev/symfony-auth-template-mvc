<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
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
            ])
            ->add('nickname', TextType::class, [
                'label' => 'Apodo',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'El apodo no puede estar vacío.'),
                    new Length(
                        min: 1,
                        max: 100,
                        minMessage: 'El apodo debe tener al menos {{ limit }} caracteres.',
                        maxMessage: 'El apodo no puede superar {{ limit }} caracteres.',
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción',
                'attr' => ['style' => 'min-height: 50px !important;'],
            ])
            ->add('profilePhotoFile', FileType::class, [
                'label' => 'Foto de perfil',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new Image(maxSize: '5M'),
                ],
            ])
            ->add('removeProfilePhoto', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => '0',
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
