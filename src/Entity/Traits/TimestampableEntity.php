<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait TimestampableEntity
{
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    // Si prefieres que sea null hasta que haya update, déjalo nullable.
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\PrePersist]
    public function timestampableOnCreate(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }

        // Decide una política:
        // 1) modifiedAt = null al crear (solo se rellena en updates)
        // 2) o igualarlo a createdAt al crear:
        // $this->modifiedAt ??= $now;
    }

    #[ORM\PreUpdate]
    public function timestampableOnUpdate(): void
    {
        $this->modifiedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    // No se exponen setters para createdAt y modifiedAt.
    // Añadir solo si es necesario (ej: fixtures).
}
