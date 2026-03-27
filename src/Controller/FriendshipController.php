<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use App\Repository\UserRepository;
use App\Service\FriendshipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/amigos')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class FriendshipController extends AbstractController
{
    public function __construct(
        private FriendshipService $friendshipService,
        private FriendshipRepository $friendshipRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
    ) {}

    // ─── Páginas ──────────────────────────────────────────────────────────────

    #[Route('', name: 'app_friends_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = $this->loadSectionData($user);

        return $this->render('friendship/index.html.twig', [
            'pendingRequests' => $data['pendingRequests'],
            'pendingSent'     => $data['pendingSent'],
            'friendItems'     => $data['friendItems'],
            'blockedItems'    => $data['blockedItems'],
            'currentUser'     => $user,
        ]);
    }

    // ─── Búsqueda AJAX ───────────────────────────────────────────────────────

    #[Route('/buscar', name: 'app_friends_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $q       = trim((string) $request->query->get('q', ''));
        $results = [];
        $friendshipMap = [];

        if (mb_strlen($q) >= 2) {
            $results = $this->userRepository->searchByNickname($q, $user);
            foreach ($results as $found) {
                $friendshipMap[$found->getId()] = $this->friendshipRepository->findBetween($user, $found);
            }
        }

        return $this->render('friendship/_search_results.html.twig', [
            'results'       => $results,
            'friendshipMap' => $friendshipMap,
            'currentUser'   => $user,
        ]);
    }

    // ─── Acciones (responden JSON {html}) ────────────────────────────────────

    #[Route('/solicitar/{userId}', name: 'app_friends_send_request', methods: ['POST'])]
    public function sendRequest(int $userId, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->requireCsrf($request, $userId);

        $target = $this->userRepository->find($userId)
            ?? throw $this->createNotFoundException('Usuario no encontrado.');

        try {
            $this->friendshipService->sendRequest($currentUser, $target);
        } catch (\LogicException) {
            // Estado ya existente — devolvemos el estado actual sin error
        }

        return $this->actionsResponse($currentUser, $target);
    }

    #[Route('/cancelar/{id}', name: 'app_friends_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $friendship = $this->findFriendshipOwnedBy($id, $currentUser);
        $target     = $friendship->getOtherUser($currentUser);

        $this->requireCsrf($request, $target->getId());

        // Si está bloqueada, solo el bloqueador (requester) puede cancelar
        if ($friendship->isBlocked()
            && $friendship->getRequester()->getId() !== $currentUser->getId()
        ) {
            throw $this->createAccessDeniedException('No puedes realizar esta acción.');
        }

        $this->em->remove($friendship);
        $this->em->flush();

        return $this->actionsResponse($currentUser, $target);
    }

    #[Route('/aceptar/{id}', name: 'app_friends_accept', methods: ['POST'])]
    public function accept(int $id, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $friendship = $this->findFriendshipOwnedBy($id, $currentUser);
        $target     = $friendship->getOtherUser($currentUser);

        $this->requireCsrf($request, $target->getId());

        try {
            $this->friendshipService->accept($friendship, $currentUser);
        } catch (\LogicException) {}

        return $this->actionsResponse($currentUser, $target);
    }

    #[Route('/rechazar/{id}', name: 'app_friends_reject', methods: ['POST'])]
    public function reject(int $id, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $friendship = $this->findFriendshipOwnedBy($id, $currentUser);
        $target     = $friendship->getOtherUser($currentUser);

        $this->requireCsrf($request, $target->getId());

        try {
            $this->friendshipService->reject($friendship, $currentUser);
        } catch (\LogicException) {}

        return $this->actionsResponse($currentUser, $target);
    }

    #[Route('/eliminar/{id}', name: 'app_friends_unfriend', methods: ['POST'])]
    public function unfriend(int $id, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $friendship = $this->findFriendshipOwnedBy($id, $currentUser);
        $target     = $friendship->getOtherUser($currentUser);

        $this->requireCsrf($request, $target->getId());

        $this->friendshipService->removeFriend($friendship);

        return $this->actionsResponse($currentUser, $target);
    }

    #[Route('/bloquear/{userId}', name: 'app_friends_block', methods: ['POST'])]
    public function block(int $userId, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->requireCsrf($request, $userId);

        $target = $this->userRepository->find($userId)
            ?? throw $this->createNotFoundException('Usuario no encontrado.');

        $this->friendshipService->block($currentUser, $target);

        return $this->actionsResponse($currentUser, $target);
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function requireCsrf(Request $request, int $userId): void
    {
        if (!$this->isCsrfTokenValid('friendship_' . $userId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }
    }

    private function findFriendshipOwnedBy(int $id, User $user): Friendship
    {
        $friendship = $this->friendshipRepository->find($id)
            ?? throw $this->createNotFoundException('Solicitud no encontrada.');

        if ($friendship->getRequester()->getId() !== $user->getId()
            && $friendship->getReceiver()->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException('No tienes acceso a esta solicitud.');
        }

        return $friendship;
    }

    private function actionsResponse(User $currentUser, User $targetUser): JsonResponse
    {
        $friendship = $this->friendshipRepository->findBetween($currentUser, $targetUser);

        $html = $this->renderView('friendship/_actions_inner.html.twig', [
            'friendship'  => $friendship,
            'targetUser'  => $targetUser,
            'currentUser' => $currentUser,
        ]);

        $data = $this->loadSectionData($currentUser);

        $sections = [
            'pending-section'      => $this->renderView('friendship/_section_pending.html.twig', [
                'pendingRequests' => $data['pendingRequests'],
                'currentUser'     => $currentUser,
            ]),
            'pending-sent-section' => $this->renderView('friendship/_section_pending_sent.html.twig', [
                'pendingSent' => $data['pendingSent'],
                'currentUser' => $currentUser,
            ]),
            'friends-section'      => $this->renderView('friendship/_section_friends.html.twig', [
                'friendItems' => $data['friendItems'],
                'currentUser' => $currentUser,
            ]),
            'blocked-section'      => $this->renderView('friendship/_section_blocked.html.twig', [
                'blockedItems' => $data['blockedItems'],
                'currentUser'  => $currentUser,
            ]),
        ];

        return $this->json([
            'html'         => $html,
            'sections'     => $sections,
            'pendingCount' => count($data['pendingRequests']),
        ]);
    }

    /**
     * Carga los datos de las cuatro secciones de la página de amigos.
     */
    private function loadSectionData(User $user): array
    {
        $friendItems = [];
        foreach ($this->friendshipRepository->findAcceptedFriends($user) as $f) {
            $friendItems[] = [
                'friendship' => $f,
                'friend'     => $f->getOtherUser($user),
            ];
        }

        return [
            'pendingRequests' => $this->friendshipRepository->findPendingReceivedBy($user),
            'pendingSent'     => $this->friendshipRepository->findPendingSentBy($user),
            'friendItems'     => $friendItems,
            'blockedItems'    => $this->friendshipRepository->findBlockedBy($user),
        ];
    }
}
