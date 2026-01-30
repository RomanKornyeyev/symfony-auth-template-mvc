<?php
// src/Form/Constraints/PasswordConstraints.php
namespace App\Form\Constraints;

use Symfony\Component\Validator\Constraints as Assert;

final class PasswordConstraints
{
    public static function newPassword(int $min = 6, int $max = 16): array
    {
        return [
            new Assert\NotBlank(message: 'Introduce una nueva contraseña.'),
            new Assert\Length(
                min: $min,
                max: $max,
                minMessage: 'La contraseña debe tener al menos {{ limit }} caracteres.',
                maxMessage: 'La contraseña no puede tener más de {{ limit }} caracteres.'
            ),
            new Assert\NotCompromisedPassword(
                message: 'Esta contraseña aparece en filtraciones conocidas. Usa otra.'
            ),
        ];
    }
}