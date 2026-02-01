<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "user")]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Este email ya está registrado.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    private ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Email(message: 'El correo electrónico no es válido.')]
    #[ORM\Column(type: "string", length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: 'El correo electrónico no es válido.')]
    private ?string $pendingEmail = null;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[Assert\NotBlank]
    #[Assert\Length(
        min: 6,
        minMessage: 'La contraseña debe tener al menos {{ limit }} caracteres'
    )]
    #[ORM\Column(type: "string")]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $status): void
    {
        $this->isVerified = $status;
    }

    public function getPendingEmail(): ?string
    {
        return $this->pendingEmail;
    }

    public function setPendingEmail(?string $pendingEmail): self
    {
        $this->pendingEmail = $pendingEmail;
        return $this;
    }

    public function hasPendingEmailChange(): bool
    {
        return $this->pendingEmail !== null;
    }

    public function eraseCredentials(): void
    {
        // Si almacenas datos temporales sensibles, límpialos aquí
        // Implementar esta función más adelante
    }
}
