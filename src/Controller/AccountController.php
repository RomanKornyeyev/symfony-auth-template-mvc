<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/cuenta')]
class AccountController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/', name: 'app_account_index')]
    public function index(): Response
    {
        // Obtener el usuario actualmente autenticado
        $user = $this->getUser();

        // Se controla en el firewall, esto es para evitar errores al invocar $user abajo
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Debes iniciar sesión para acceder a tu cuenta.');
        }

        // data
        $name = $user->getName();
        $email = $user->getEmail();
        $emailVerified = $user->isVerified();
        $pendingEmail = $user->isVerified();
        $createdAt = $user->getCreatedAt();
        $modifiedAt = $user->getModifiedAt();

        return $this->render('account/index.html.twig', [
            'account' => [
                'name' => $name,
                'email' => $email,
                'emailVerified' => $emailVerified,
                'pendingEmail' => $pendingEmail,
                'createdAt' => $createdAt,
                'modifiedAt' => $modifiedAt,
            ],
        ]);
    }

    #[Route('/editar', name: 'app_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user, [
            'csrf_token_id' => 'account_edit_profile',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // guardar en la bd
            $em->flush(); 

            // mensaje de éxito
            $this->addFlash('success', 'Nombre actualizado correctamente.');
            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('account/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
