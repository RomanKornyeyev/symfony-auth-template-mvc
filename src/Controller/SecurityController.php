<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserToken;
use App\Form\RegisterType;
use App\Form\ResetPasswordType;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

#[Route('/auth')]
class SecurityController extends AbstractController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si el user ya está autenticado, redirigir a la página de inicio
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        // Obtener error de autenticación, si hay
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Symfony maneja el logout automáticamente
        throw new \Exception('Bye =)'); // El logout debe estar activado en security.yaml
    }

    #[Route('/registro', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        // Si el usuario ya está autenticado, redirigir a la página de inicio
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Crear usuario
                $this->userService->registerUser($user);

                $this->addFlash('success', 'Registrado con éxito. Confirma tu email para poder iniciar sesión.');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Hubo un error al registrar la cuenta: ' . $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView()
        ]);
    }

    // HACER QUE AUTENTIQUE AUTOMÁTICAMENTE AL USUARIO AL CONFIRMAR SU EMAIL
    #[Route('/confirmar-cuenta', name: 'app_confirm_email')]
    public function confirmEmail(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        // Si el usuario ya está autenticado, redirigir a la página de inicio
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        $tokenValue = $request->query->get('token');

        if (!$tokenValue) {
            throw $this->createNotFoundException('Token no proporcionado.');
        }

        // Buscar el token en la base de datos
        $token = $em->getRepository(UserToken::class)->findOneBy(['token' => $tokenValue]);

        if (!$token) {
            throw $this->createNotFoundException('Token inválido.');
        }

        if ($token->isExpired()) {
            throw $this->createAccessDeniedException('El token ha expirado.');
        }

        if ($token->isUsed()) {
            throw $this->createAccessDeniedException('El token ya fue utilizado.');
        }

        // Marcar el usuario como verificado
        $user = $token->getUser();
        $user->setIsVerified(true);

        // Marcar el token como usado y guardar cambios
        $token->markAsUsed();
        $em->flush();

        // 4. Autenticar automáticamente al usuario (retirar los 2 últimos params para no activar remember me)
        $security->login($user, 'form_login', 'main', [(new RememberMeBadge())->enable()]);

        // Mensaje de éxito, login y redirección
        $this->addFlash('success', 'Tu cuenta ha sido confirmada correctamente. Has iniciado sesión.');
        return $this->redirectToRoute('app_index');

        // sin login automático
        // $this->addFlash('success', 'Tu cuenta ha sido confirmada correctamente. Ahora puedes iniciar sesión.');
        // return $this->redirectToRoute('app_login');
    }

    #[Route('/reenviar-confirmacion', name: 'app_resend_confirmation')]
    public function resendConfirmation(Request $request, EntityManagerInterface $em, UserService $userService, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Si el usuario ya está autenticado, redirigir a la página de inicio
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $csrfToken = $request->request->get('_csrf_token');

            // Validar el token CSRF
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('resend_confirmation', $csrfToken))) {
                // throw $this->createAccessDeniedException('CSRF token inválido.'); // es más estricto, pero menos amigable que un flash
                $this->addFlash('danger', 'Token CSRF inválido.');
                return $this->redirectToRoute('app_resend_confirmation');
            }

            if (!$email) {
                $this->addFlash('danger', 'Por favor, introduce tu email.');
                return $this->redirectToRoute('app_resend_confirmation');
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('danger', 'No hay ninguna cuenta con este email.');
                return $this->redirectToRoute('app_resend_confirmation');
            }

            if ($user->isVerified()) {
                $this->addFlash('warning', 'Tu cuenta ya está confirmada. Puedes iniciar sesión.');
                return $this->redirectToRoute('app_login');
            }

            // Generar un nuevo token y reenviar el email
            $userService->generateNewVerificationToken($user);

            $this->addFlash('success', 'Hemos enviado un nuevo email de confirmación.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/resend_confirmation.html.twig');
    }

    #[Route('/recuperar-contrasena', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, EntityManagerInterface $em, UserService $userService, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Si el usuario ya está autenticado, redirigir a la página de inicio
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('app_index');
        // }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $csrfToken = $request->request->get('_csrf_token');

            // Validar el token CSRF
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('forgot_password', $csrfToken))) {
                // throw $this->createAccessDeniedException('CSRF token inválido.'); // es más estricto, pero menos amigable que un flash
                $this->addFlash('danger', 'Token CSRF inválido.');
                return $this->redirectToRoute('app_forgot_password');
            }

            if (!$email) {
                $this->addFlash('danger', 'Por favor, introduce tu email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('danger', 'Si el email existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Generar token de recuperación
            $userService->generateResetPasswordToken($user);

            $this->addFlash('success', 'Si el email es válido, recibirás un enlace para restablecer tu contraseña.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/restablecer-contrasena', name: 'app_reset_password')]
    public function resetPassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Si el usuario ya está autenticado, redirigir a la página de inicio
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        $tokenValue = $request->query->get('token');

        if (!$tokenValue) {
            throw $this->createNotFoundException('Token no proporcionado.');
        }

        // Buscar el token en la base de datos
        $token = $em->getRepository(UserToken::class)->findOneBy(['token' => $tokenValue, 'type' => 'password_reset', 'used' => false]);

        if (!$token) {
            throw $this->createNotFoundException('Token inválido o ya utilizado.');
        }

        if ($token->isExpired()) {
            throw $this->createAccessDeniedException('El token ha expirado.');
        }

        $user = $token->getUser();

        $form = $this->createForm(ResetPasswordType::class, null, [
            'csrf_token_id' => 'account_change_password',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainNew = (string) $form->get('newPassword')->getData();

            // Evitar reutilizar la misma contraseña (comparando contra el hash actual)
            if ($passwordHasher->isPasswordValid($user, $plainNew)) {
                $this->addFlash('danger', 'La nueva contraseña no puede ser igual a la actual.');
                return $this->redirectToRoute('app_reset_password', ['token' => $tokenValue]);
            }

            // actualizar la contraseña y mandar mail aviso
            $this->userService->changePassword($user, $plainNew);
            
            // Marcar el token como usado y guardar cambios
            $token->markAsUsed();
            $em->flush();

            $this->addFlash('success', 'Tu contraseña ha sido restablecida correctamente. Ahora puedes iniciar sesión.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $tokenValue,
            'form' => $form->createView()
        ]);
    }
}
