<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table(name: "user_token")]
#[ORM\HasLifecycleCallbacks]
class UserToken
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private User $user;

    #[ORM\Column(type: "string", unique: true)]
    #[Assert\NotBlank]
    private string $token;

    #[ORM\Column(type: "string")]
    #[Assert\Choice(["registration", "password_reset"])]
    private string $type;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotBlank]
    private \DateTime $expiresAt;

    #[ORM\Column(type: "boolean")]
    private bool $used = false;

    public function __construct(User $user, string $type = "registration")
    {
        $this->user = $user;
        $this->type = $type;
        $this->token = bin2hex(random_bytes(32)); // Genera un token aleatorio
        $this->expiresAt = (new \DateTime())->modify('+24 hours');
    }

    public function isExpired(): bool
    {
        return new \DateTime() > $this->expiresAt;
    }

    public function markAsUsed(): void
    {
        $this->used = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): self
    {
        $this->used = $used;
        return $this;
    }
}
