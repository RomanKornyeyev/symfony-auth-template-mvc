<?php
namespace App\Service;

use App\Entity\Usuario;
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

  public function registerUser(Usuario $user): void
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

    // Persistir usuario y token en la BD
    $this->em->persist($user);
    $this->em->persist($token);
    $this->em->flush();

    // Enviar email de confirmación
    $this->mailService->sendConfirmationEmail($user->getEmail(), $token->getToken(), $user->getNombre());
  }

  public function generateNewVerificationToken(Usuario $user): void
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
    $this->mailService->sendConfirmationEmail($user->getEmail(), $newToken->getToken(), $user->getNombre());
  }

  public function generateResetPasswordToken(Usuario $user): void
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
    $this->mailService->sendResetPasswordEmail($user->getEmail(), $newToken->getToken());
  }

}
