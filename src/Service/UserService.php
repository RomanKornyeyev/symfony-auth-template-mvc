<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\UserToken;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
  private EntityManagerInterface $em;
  private MailService $mailService;
  private UserPasswordHasherInterface $passwordHasher;

  public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, MailService $mailService)
  {
    $this->em = $em;
    $this->mailService = $mailService;
    $this->passwordHasher = $passwordHasher;
  }

  public function registerUser(User $user): void
  {
    // Hashear la contraseña
    $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
    $user->setPassword($hashedPassword);

    // Asignar rol por defecto
    $user->setRoles(["ROLE_USER"]);

    // Generar un token único
    do {
      $token = new UserToken($user);
    } while ($this->em->getRepository(UserToken::class)->findOneBy(['token' => $token->getToken()]));

    // Persistir User y token en la BD
    $this->em->persist($user);
    $this->em->persist($token);
    $this->em->flush();

    // Enviar email de confirmación
    $this->mailService->sendConfirmationEmail($user->getEmail(), $token->getToken(), $user->getName());
  }

  public function generateNewVerificationToken(User $user): void
  {
    // Eliminar tokens antiguos si existen
    $existingToken = $this->em->getRepository(UserToken::class)->findOneBy([
      'user' => $user,
      'type' => 'registration',
      'used' => false
    ]);

    if ($existingToken) {
      $this->em->remove($existingToken);
    }

    // Crear nuevo token
    $newToken = new UserToken($user, 'registration');

    // Guardar en la BD
    $this->em->persist($newToken);
    $this->em->flush();

    // Enviar email con el nuevo token
    $this->mailService->sendConfirmationEmail($user->getEmail(), $newToken->getToken(), $user->getName());
  }

  public function generateResetPasswordToken(User $user): void
  {
    // Eliminar tokens antiguos
    $existingToken = $this->em->getRepository(UserToken::class)->findOneBy([
      'user' => $user,
      'type' => 'password_reset',
      'used' => false
    ]);

    if ($existingToken) {
      $this->em->remove($existingToken);
    }

    // Crear nuevo token
    $newToken = new UserToken($user, 'password_reset');

    // Guardar en la BD
    $this->em->persist($newToken);
    $this->em->flush();

    // Enviar email con el enlace de recuperación
    $this->mailService->sendResetPasswordEmail($user->getEmail(), $newToken->getToken(), $user->getName());
  }

  public function changePassword(User $user, string $plainNewPassword): void
  {
    // Hashear y guardar nueva contraseña
    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainNewPassword);
    $user->setPassword($hashedPassword);

    // Invalidar tokens de reset pendientes (no usados)
    $tokens = $this->em->getRepository(UserToken::class)->findBy([
      'user' => $user,
      'type' => 'password_reset',
      'used' => false,
    ]);

    foreach ($tokens as $token) {
      $this->em->remove($token);
    }

    $this->em->flush();

    // Notificación por email (añade el método en MailService)
    $this->mailService->sendPasswordChangedEmail($user->getEmail(), $user->getName());
  }

}
