<?php

namespace App\Service;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;

class FriendshipService
{
    public function __construct(
        private EntityManagerInterface $em,
        private FriendshipRepository $friendshipRepository,
    ) {}

    /**
     * Envía solicitud de amistad.
     * Valida: no auto-solicitud, no duplicados, no bloqueados,
     * receptor permite solicitudes y es buscable.
     */
    public function sendRequest(User $requester, User $receiver): Friendship
    {
        if ($requester === $receiver) {
            throw new \LogicException('No puedes enviarte una solicitud a ti mismo.');
        }

        $settings = $receiver->getSettings();
        if ($settings && !$settings->isSearchable()) {
            throw new \LogicException('Este usuario no está disponible.');
        }

        if ($settings && !$settings->allowFriendRequests()) {
            throw new \LogicException('Este usuario no acepta solicitudes de amistad.');
        }

        $existing = $this->friendshipRepository->findBetween($requester, $receiver);

        if ($existing) {
            if ($existing->isBlocked()) {
                throw new \LogicException('No se puede enviar la solicitud.');
            }
            if ($existing->isAccepted()) {
                throw new \LogicException('Ya sois amigos.');
            }
            if ($existing->isPending()) {
                throw new \LogicException('Ya existe una solicitud pendiente.');
            }
            // Si fue rejected, eliminamos y permitimos reenviar
            $this->em->remove($existing);
            $this->em->flush();
        }

        $friendship = new Friendship($requester, $receiver);
        $this->em->persist($friendship);
        $this->em->flush();

        return $friendship;
    }

    /**
     * Acepta una solicitud pendiente. Solo el receiver puede aceptar.
     */
    public function accept(Friendship $friendship, User $currentUser): void
    {
        if ($friendship->getReceiver() !== $currentUser) {
            throw new \LogicException('Solo el receptor puede aceptar la solicitud.');
        }

        if (!$friendship->isPending()) {
            throw new \LogicException('Esta solicitud ya no está pendiente.');
        }

        $friendship->accept();
        $this->em->flush();
    }

    /**
     * Rechaza una solicitud pendiente. Solo el receiver puede rechazar.
     */
    public function reject(Friendship $friendship, User $currentUser): void
    {
        if ($friendship->getReceiver() !== $currentUser) {
            throw new \LogicException('Solo el receptor puede rechazar la solicitud.');
        }

        $friendship->reject();
        $this->em->flush();
    }

    /**
     * Bloquea a un usuario. Cualquiera de los dos puede bloquear.
     */
    public function block(User $blocker, User $blocked): void
    {
        $existing = $this->friendshipRepository->findBetween($blocker, $blocked);

        // Siempre recreamos con blocker como requester para poder identificar
        // quién bloqueó a quién en la capa de presentación.
        // Flush separado para garantizar DELETE antes de INSERT y evitar
        // violación de la unique constraint (requester_id, receiver_id).
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        $friendship = new Friendship($blocker, $blocked);
        $friendship->block();
        $this->em->persist($friendship);
        $this->em->flush();
    }

    /**
     * Elimina una amistad aceptada (unfriend).
     */
    public function removeFriend(Friendship $friendship): void
    {
        $this->em->remove($friendship);
        $this->em->flush();
    }

    /**
     * Comprueba si dos usuarios son amigos (status accepted).
     */
    public function areFriends(User $userA, User $userB): bool
    {
        $friendship = $this->friendshipRepository->findBetween($userA, $userB);
        return $friendship !== null && $friendship->isAccepted();
    }
}
