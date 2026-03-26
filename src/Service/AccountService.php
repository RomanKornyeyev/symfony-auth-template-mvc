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

        $this->tokenRepo->invalidateActiveEmailChangeTokensForUser($user);

        $user->setPendingEmail($newEmail);

        $token = new UserToken($user, UserToken::TYPE_EMAIL_CHANGE_AUTHORIZE);
        $this->em->persist($token);
        $this->em->flush();

        $this->mailService->sendEmailChangeAuthorizeToCurrentEmail(
            (string) $user->getEmail(),
            $token->getToken(),
            (string) $user->getName(),
            $newEmail
        );
    }

    /**
     * Paso 1: el usuario hace clic en el link del email actual.
     * Se crea el token de confirmación y se envía al nuevo email.
     */
    public function authorizeEmailChange(string $tokenValue): void
    {
        $userToken = $this->tokenRepo->findValidToken($tokenValue, UserToken::TYPE_EMAIL_CHANGE_AUTHORIZE);
        if (!$userToken) {
            throw new \DomainException('El enlace de autorización no es válido o ha caducado.');
        }

        $user = $userToken->getUser();

        if (!$user->hasPendingEmailChange()) {
            $userToken->markAsUsed();
            $this->em->flush();
            throw new \DomainException('No hay ningún cambio de email pendiente.');
        }

        $newEmail = (string) $user->getPendingEmail();

        $this->assertEmailAvailable($newEmail, $user);

        $userToken->markAsUsed();

        $confirmToken = new UserToken($user, UserToken::TYPE_EMAIL_CHANGE_CONFIRM);
        $this->em->persist($confirmToken);
        $this->em->flush();

        $this->mailService->sendEmailChangeConfirmToNewEmail(
            $newEmail,
            $confirmToken->getToken(),
            (string) $user->getName()
        );
    }

    /**
     * Paso 2: el usuario hace clic en el link del nuevo email.
     * Se actualiza el email definitivamente.
     */
    public function confirmEmailChange(string $tokenValue): User
    {
        $userToken = $this->tokenRepo->findValidToken($tokenValue, UserToken::TYPE_EMAIL_CHANGE_CONFIRM);
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

        $this->assertEmailAvailable($newEmail, $user);

        $user->setEmail($newEmail);
        $user->setPendingEmail(null);

        $userToken->markAsUsed();
        $this->em->flush();

        $this->mailService->sendEmailChangeCompleted($oldEmail, $newEmail, (string) $user->getName());

        $this->em->refresh($user);
        return $user;
    }

    public function resendEmailChange(User $user): void
    {
        if (!$user->hasPendingEmailChange()) {
            throw new \DomainException('No hay ningún cambio de email pendiente.');
        }

        $isStep1 = $this->tokenRepo->findActiveAuthorizeTokenForUser($user) !== null;

        $this->tokenRepo->invalidateActiveEmailChangeTokensForUser($user);

        $newEmail = (string) $user->getPendingEmail();

        if ($isStep1) {
            $token = new UserToken($user, UserToken::TYPE_EMAIL_CHANGE_AUTHORIZE);
            $this->em->persist($token);
            $this->em->flush();
            $this->mailService->sendEmailChangeAuthorizeToCurrentEmail(
                (string) $user->getEmail(),
                $token->getToken(),
                (string) $user->getName(),
                $newEmail
            );
        } else {
            $token = new UserToken($user, UserToken::TYPE_EMAIL_CHANGE_CONFIRM);
            $this->em->persist($token);
            $this->em->flush();
            $this->mailService->sendEmailChangeConfirmToNewEmail(
                $newEmail,
                $token->getToken(),
                (string) $user->getName()
            );
        }
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

    /**
     * Devuelve el paso actual del flujo de cambio de email:
     * 1 = esperando autorización (link en email actual)
     * 2 = esperando confirmación (link en nuevo email)
     * 0 = sin cambio pendiente
     */
    public function getEmailChangeStep(User $user): int
    {
        if (!$user->hasPendingEmailChange()) {
            return 0;
        }

        return $this->tokenRepo->findActiveAuthorizeTokenForUser($user) !== null ? 1 : 2;
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
