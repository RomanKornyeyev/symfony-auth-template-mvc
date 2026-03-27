<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FriendshipRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class UserProfileController extends AbstractController
{
    #[Route('/usuarios/{id}', name: 'app_user_profile')]
    public function profile(
        int $id,
        UserRepository $userRepository,
        FriendshipRepository $friendshipRepository,
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $targetUser = $userRepository->find($id)
            ?? throw $this->createNotFoundException('Usuario no encontrado.');

        // Auto-perfil → redirigir a la cuenta propia
        if ($targetUser->getId() === $currentUser->getId()) {
            return $this->redirectToRoute('app_account_index');
        }

        $friendship = $friendshipRepository->findBetween($currentUser, $targetUser);

        return $this->render('users/profile.html.twig', [
            'targetUser'  => $targetUser,
            'currentUser' => $currentUser,
            'friendship'  => $friendship,
        ]);
    }
}
