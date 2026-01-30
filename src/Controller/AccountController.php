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
use App\Form\ChangePasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Cache\CacheInterface;

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

    #[Route('/password', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(service: 'limiter.change_password')]
        RateLimiterFactory $changePasswordLimiter,
        CacheInterface $cache
    ): Response {
        $user = $this->getUser();

        // Se controla en el firewall, esto es para evitar posibles errores al invocar $user abajo
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Debes iniciar sesión para acceder a tu cuenta.');
        }

        $form = $this->createForm(ChangePasswordType::class, null, [
            'csrf_token_id' => 'account_change_password',
        ]);

        $form->handleRequest($request);
        
        $ip = $request->getClientIp() ?? 'unknown';
        $limiterKey = 'user_'.$user->getId().'_ip_'.$ip;
        $lockKey = 'lock_change_password_'.$limiterKey;

        // Rate limiting
        if ($request->isMethod('POST')) {
            $blockedUntilTs = $cache->get($lockKey, fn() => 0);

            if (is_int($blockedUntilTs) && $blockedUntilTs > time()) {
                $this->addFlash(
                    'danger_html', // evita escape
                    'Demasiados intentos. Vuelve a intentarlo en unos minutos o <a href="'.$this->generateUrl('app_forgot_password').'">recupera tu contraseña</a>.'
                );
                return $this->redirectToRoute('app_account_password');
            }
        }
        
        if ($form->isSubmitted() && !$form->isValid()) {
            if ($form->get('currentPassword')->getErrors()->count() > 0) {
                $limiter = $changePasswordLimiter->create($limiterKey);
                $limit = $limiter->consume(1);

                if (!$limit->isAccepted()) {
                    $retryAfter = $limit->getRetryAfter(); // DateTimeImmutable
                    $cache->delete($lockKey);
                    $cache->get(
                        $lockKey,
                        fn() => $retryAfter->getTimestamp()
                    );

                    $this->addFlash(
                        'danger_html', // evita escape
                        'Demasiados intentos. Vuelve a intentarlo en unos minutos o <a href="'.$this->generateUrl('app_forgot_password').'">recupera tu contraseña</a>.'
                    );
                    return $this->redirectToRoute('app_account_password');
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $plainNew = (string) $form->get('newPassword')->getData();

            // Evitar reutilizar la misma contraseña (comparando contra el hash actual)
            if ($passwordHasher->isPasswordValid($user, $plainNew)) {
                $this->addFlash('danger', 'La nueva contraseña no puede ser igual a la actual.');
                return $this->redirectToRoute('app_account_password');
            }

            // actualizar la contraseña y mandar mail aviso
            $this->userService->changePassword($user, $plainNew);

            $this->addFlash('success', 'Contraseña actualizada correctamente.');
            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('account/password.html.twig', [
            'form' => $form,
        ]);
    }
}
