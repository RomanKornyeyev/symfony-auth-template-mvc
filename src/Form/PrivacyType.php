<?php

namespace App\Form;

use App\Entity\UserSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrivacyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isSearchable', CheckboxType::class, [
                'label'    => 'Permitir que otros usuarios me encuentren en búsquedas',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])
            ->add('allowFriendRequests', CheckboxType::class, [
                'label'    => 'Permitir que otros usuarios me envíen solicitudes de amistad',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'       => UserSettings::class,
            'csrf_protection'  => true,
            'csrf_field_name'  => '_token',
            'csrf_token_id'    => 'account_privacy',
        ]);
    }
}
