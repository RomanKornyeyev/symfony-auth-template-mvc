<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailService
{
  private MailerInterface $mailer;
  private UrlGeneratorInterface $router;

  public function __construct(MailerInterface $mailer, UrlGeneratorInterface $router)
  {
    $this->mailer = $mailer;
    $this->router = $router;
  }

  public function sendConfirmationEmail(string $to, string $token, string $name = ''): void
  {
    $url = $this->router->generate('app_confirm_email', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

    $email = (new TemplatedEmail())
      ->from('no.reply.financeflow.team@gmail.com')
      ->to($to)
      ->subject('Confirma tu cuenta')
      ->htmlTemplate('email/confirm_email.html.twig')
      ->context([
        'url_confirmacion_email' => $url,
        'name' => $name
      ]);

    $this->mailer->send($email);
  }

  public function sendResetPasswordEmail(string $to, string $token, string $name = ''): void
  {
    $url = $this->router->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

    $email = (new TemplatedEmail())
      ->from('no.reply.financeflow.team@gmail.com')
      ->to($to)
      ->subject('Recuperación de contraseña')
      ->htmlTemplate('email/reset_password.html.twig')
      ->context([
        'resetPasswordUrl' => $url,
        'name' => $name
      ]);

    $this->mailer->send($email);
  }

  public function sendPasswordChangedEmail(string $to, string $name = ''): void
  {
    $url = $this->router->generate('app_forgot_password', [], UrlGeneratorInterface::ABSOLUTE_URL);

    $email = (new TemplatedEmail())
      ->from('no.reply.financeflow.team@gmail.com')
      ->to($to)
      ->subject('Contraseña modificada')
      ->htmlTemplate('email/password_changed.html.twig')
      ->context([
        'resetPasswordUrl' => $url,
        'name' => $name
      ]);

    $this->mailer->send($email);
  }

  public function sendEmailChangeConfirmationToCurrentEmail(
    string $to,
    string $token,
    string $name = '',
    string $pendingEmail = ''
  ): void {

    $url = $this->router->generate('app_account_email_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
    $resetPasswordUrl = $this->router->generate('app_forgot_password', [], UrlGeneratorInterface::ABSOLUTE_URL);

    $email = (new TemplatedEmail())
      ->from('no.reply.financeflow.team@gmail.com')
      ->to($to)
      ->subject('Confirma el cambio de correo')
      ->htmlTemplate('email/email_change_confirm_current.html.twig')
      ->context([
        'confirmUrl' => $url,
        'resetPasswordUrl' => $resetPasswordUrl,
        'pendingEmail' => $pendingEmail,
        'name' => $name,
      ]);

    $this->mailer->send($email);
  }

  public function sendEmailChangeCancelled(string $to, string $name = '', string $pendingEmail = ''): void
  {
    $resetPasswordUrl = $this->router->generate('app_forgot_password', [], UrlGeneratorInterface::ABSOLUTE_URL);

    $email = (new TemplatedEmail())
      ->from('no.reply.financeflow.team@gmail.com')
      ->to($to)
      ->subject('Cambio de correo cancelado')
      ->htmlTemplate('email/email_change_cancelled.html.twig')
      ->context([
        'resetPasswordUrl' => $resetPasswordUrl,
        'pendingEmail' => $pendingEmail,
        'name' => $name,
      ]);

    $this->mailer->send($email);
  }

  public function sendEmailChangeCompleted(string $oldEmail, string $newEmail, string $name = ''): void
  {
    $resetPasswordUrl = $this->router->generate('app_forgot_password', [], UrlGeneratorInterface::ABSOLUTE_URL);

    $context = [
      'oldEmail' => $oldEmail,
      'newEmail' => $newEmail,
      'resetPasswordUrl' => $resetPasswordUrl,
      'name' => $name,
    ];

    $emailToOld = (new TemplatedEmail())
      ->from('no.reply.financeflow.team@gmail.com')
      ->to($oldEmail)
      ->subject('Correo actualizado')
      ->htmlTemplate('email/email_change_completed.html.twig')
      ->context($context);

    $emailToNew = (new TemplatedEmail())
      ->from('no.reply.financeflow.team@gmail.com')
      ->to($newEmail)
      ->subject('Correo actualizado')
      ->htmlTemplate('email/email_change_completed.html.twig')
      ->context($context);

    $this->mailer->send($emailToOld);
    $this->mailer->send($emailToNew);
  }
}
