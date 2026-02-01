<?php
// src/Form/ChangeEmailType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newEmail', EmailType::class, [
                'label' => 'Nuevo correo electrónico',
                'mapped' => false,
                'attr' => ['autocomplete' => 'email'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Introduce un correo electrónico.'),
                    new Assert\Email(message: 'El correo electrónico no es válido.'),
                    new Assert\Length(max: 180, maxMessage: 'El email no puede superar {{ limit }} caracteres.'),
                ],
            ]);
    }
}
