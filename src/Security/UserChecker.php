<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserChecker implements UserCheckerInterface
{
  public function checkPreAuth(UserInterface $user): void
  {
    if (!$user instanceof \App\Entity\User) {
      return;
    }

    if (!$user->isVerified()) {
      throw new CustomUserMessageAccountStatusException('Debes confirmar tu email antes de iniciar sesión.');
    }
  }

  public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
  {
    // Se ejecuta después de la autenticación, pero no necesitamos lógica aquí.
  }
}
