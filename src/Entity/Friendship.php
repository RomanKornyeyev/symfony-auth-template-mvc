<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: "App\Repository\FriendshipRepository")]
#[ORM\Table(name: "friendship")]
#[ORM\UniqueConstraint(name: "unique_friendship", columns: ["requester_id", "receiver_id"])]
#[ORM\HasLifecycleCallbacks]
class Friendship
{
    use TimestampableEntity;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BLOCKED = 'blocked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private User $requester;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private User $receiver;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    public function __construct(User $requester, User $receiver)
    {
        $this->requester = $requester;
        $this->receiver = $receiver;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequester(): User
    {
        return $this->requester;
    }

    public function getReceiver(): User
    {
        return $this->receiver;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function accept(): self
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->respondedAt = new \DateTimeImmutable();
        return $this;
    }

    public function reject(): self
    {
        $this->status = self::STATUS_REJECTED;
        $this->respondedAt = new \DateTimeImmutable();
        return $this;
    }

    public function block(): self
    {
        $this->status = self::STATUS_BLOCKED;
        $this->respondedAt = new \DateTimeImmutable();
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function getOtherUser(User $currentUser): User
    {
        return $this->requester === $currentUser ? $this->receiver : $this->requester;
    }
}
