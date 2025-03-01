<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Constraints comunes para todos los campos de texto
        $commonConstraints = [
          new Length(['max' => 255, 'maxMessage' => 'Este campo no puede tener más de {{ limit }} caracteres']),
          new Regex([
              'pattern' => '/^[^<>]*$/',
              'message' => 'Este campo no puede contener los caracteres "<" o ">"'
          ])
        ];

        $requiredConstraints = array_merge([
            new NotBlank(['message' => 'Este campo es obligatorio']),
        ], $commonConstraints);

        $notBlankConstraint = [
            new NotBlank(['message' => 'Este campo es obligatorio']),
        ];

        $builder
            ->add('nombre', TextType::class, [
              'required' => false,
              'label' => 'Nombre',
              'attr' => ['class' => 'form-control'],
              'constraints' => $requiredConstraints,
            ])
            ->add('email', EmailType::class, [
              'required' => false,
              'label' => 'Correo electrónico',
              'attr' => ['class' => 'form-control'],
              'constraints' => $requiredConstraints,
            ])
            ->add('password', PasswordType::class, [
              'required' => false,
              'label' => 'Contraseña',
              'attr' => ['class' => 'form-control'],
              'constraints' => $requiredConstraints,
            ])
            ->add('comentarios', TextareaType::class, [
              'required' => false,
              'label' => 'Comentarios',
              'attr' => ['class' => 'form-control', 'rows' => 8],
              'constraints' => $requiredConstraints,
            ])
            ->add('aceptar_terminos', CheckboxType::class, [
              'required' => false,
              'label' => 'Aceptar términos y condiciones',
              'constraints' => [
                    new IsTrue(['message' => 'Debes aceptar los términos y condiciones']),
                ],
            ])
            ->add('opciones', ChoiceType::class, [
              'required' => false,
              'label' => 'Seleccione una opción',
              'choices' => [
                'Opción 1' => 'opcion_1',
                'Opción 2' => 'opcion_2',
                'Opción 3' => 'opcion_3',
              ],
              'expanded' => false, // Dropdown en lugar de radio buttons
              'multiple' => false, // Solo se puede seleccionar una opción
              'attr' => ['class' => 'form-select'],
              'constraints' => $requiredConstraints,
            ])
            ->add('registrarse', SubmitType::class, [
              'label' => 'Registrarse',
              'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true, // Activar protección CSRF
            'csrf_field_name' => '_csrf_token', // Nombre del campo oculto
        ]);
    }
}