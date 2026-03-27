<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\FriendshipRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FriendshipExtension extends AbstractExtension
{
    public function __construct(
        private FriendshipRepository $friendshipRepository,
        private Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_friend_requests_count', $this->getPendingCount(...)),
        ];
    }

    public function getPendingCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return count($this->friendshipRepository->findPendingReceivedBy($user));
    }
}
