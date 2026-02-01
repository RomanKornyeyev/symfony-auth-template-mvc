<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccountService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserTokenRepository $tokenRepo,
        private MailService $mailService,
    ) {}

    public function requestEmailChange(User $user, string $newEmail): void
    {
        $newEmail = mb_strtolower(trim($newEmail));
        $currentEmail = mb_strtolower((string) $user->getEmail());

        if ($user->hasPendingEmailChange()) {
            throw new \DomainException('Ya tienes un cambio de email pendiente. Puedes reenviar la confirmación o cancelarlo.');
        }

        if ($newEmail === $currentEmail) {
            throw new \DomainException('El nuevo email no puede ser igual al actual.');
        }

        $this->assertEmailAvailable($newEmail);

        // Un solo token activo -> marcamos usados (invalidamos) los anteriores (histórico)
        $this->tokenRepo->invalidateActiveEmailChangeTokensForUser($user);

        $user->setPendingEmail($newEmail);

        $token = new UserToken($user, UserToken::TYPE_EMAIL_CHANGE);
        $this->em->persist($token);
        $this->em->flush();

        $this->mailService->sendEmailChangeConfirmationToCurrentEmail(
            (string) $user->getEmail(),
            $token->getToken(),
            (string) $user->getName(),
            $newEmail
        );
    }

    public function resendEmailChange(User $user): void
    {
        if (!$user->hasPendingEmailChange()) {
            throw new \DomainException('No hay ningún cambio de email pendiente.');
        }

        $this->tokenRepo->invalidateActiveEmailChangeTokensForUser($user);

        $token = new UserToken($user, UserToken::TYPE_EMAIL_CHANGE);
        $this->em->persist($token);
        $this->em->flush();

        $this->mailService->sendEmailChangeConfirmationToCurrentEmail(
            (string) $user->getEmail(),
            $token->getToken(),
            (string) $user->getName(),
            (string) $user->getPendingEmail()
        );
    }

    public function cancelEmailChange(User $user): void
    {
        if (!$user->hasPendingEmailChange()) {
            throw new \DomainException('No hay ningún cambio de email pendiente.');
        }

        $pending = (string) $user->getPendingEmail();

        $this->tokenRepo->invalidateActiveEmailChangeTokensForUser($user);
        $user->setPendingEmail(null);
        $this->em->flush();

        $this->mailService->sendEmailChangeCancelled(
            (string) $user->getEmail(),
            (string) $user->getName(),
            $pending
        );
    }

    public function confirmEmailChange(string $tokenValue): User
    {
        $userToken = $this->tokenRepo->findValidToken($tokenValue, UserToken::TYPE_EMAIL_CHANGE);
        if (!$userToken) {
            throw new \DomainException('El enlace de confirmación no es válido o ha caducado.');
        }

        $user = $userToken->getUser();

        if (!$user->hasPendingEmailChange()) {
            $userToken->markAsUsed();
            $this->em->flush();
            throw new \DomainException('No hay ningún cambio de email pendiente.');
        }

        $oldEmail = (string) $user->getEmail();
        $newEmail = (string) $user->getPendingEmail();

        // Re-validación por si hubo carrera
        $this->assertEmailAvailable($newEmail, $user);

        $user->setEmail($newEmail);
        $user->setPendingEmail(null);

        $userToken->markAsUsed();
        $this->em->flush();

        $this->mailService->sendEmailChangeCompleted($oldEmail, $newEmail, (string) $user->getName());

        // Retornamos el usuario para que el controlador pueda iniciar sesión si es necesario
        // Reload de usuario para actualizar el nuevo mail
        $this->em->refresh($user);
        return $user;
    }

    private function assertEmailAvailable(string $email, ?User $excludeUser = null): void
    {
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing !== null) {
            if ($excludeUser === null || $existing->getId() !== $excludeUser->getId()) {
                throw new \DomainException('Este email ya está registrado.');
            }
        }

        $pendingOwner = $this->em->getRepository(User::class)->findOneBy(['pendingEmail' => $email]);
        if ($pendingOwner !== null) {
            if ($excludeUser === null || $pendingOwner->getId() !== $excludeUser->getId()) {
                throw new \DomainException('Este email ya está siendo usado en un cambio pendiente.');
            }
        }
    }

}
