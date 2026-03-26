<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfilePhotoManager
{
    public function __construct(
        private readonly string $userProfilePhotoDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Guarda una nueva foto de perfil y devuelve la ruta relativa para guardar en BD,
     * p. ej.: "media/users/profile_photo/15/a1b2c3d4.webp"
     */
    public function storeForUser(int $userId, UploadedFile $file): string
    {
        $targetDir = rtrim($this->userProfilePhotoDir, '/\\') . DIRECTORY_SEPARATOR . $userId;

        if (!$this->filesystem->exists($targetDir)) {
            $this->filesystem->mkdir($targetDir, 0775);
        }

        // Extensión segura (desde mime). Si no se puede, usa la original como fallback.
        $ext = $file->guessExtension();
        if (!$ext) {
            $ext = $file->getClientOriginalExtension() ?: 'bin';
        }
        $ext = strtolower($ext);

        // Nombre no predecible, sin PII, y friendly con caché (cambia cada vez).
        $hash = bin2hex(random_bytes(16));
        $filename = $hash . '.' . $ext;

        $file->move($targetDir, $filename);

        // Ruta relativa (para asset())
        return 'media/users/profile_photo/' . $userId . '/' . $filename;
    }

    /**
     * Borra una foto anterior si existe y NO es el default global.
     * $relativePath ejemplo: "media/users/profile_photo/15/abc.webp"
     */
    public function deleteIfUserOwned(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        // Nunca borrar el default global
        if ($relativePath === 'media/users/profile_photo/default.jpg') {
            return;
        }

        $absolutePath = rtrim(dirname($this->userProfilePhotoDir), '/\\') . DIRECTORY_SEPARATOR
            . basename(dirname($this->userProfilePhotoDir)); // <- no usar esto (ver abajo)
    }

    public function deleteRelativePath(?string $relativePath, string $projectDir): void
    {
        if (!$relativePath) {
            return;
        }

        if ($relativePath === 'media/users/profile_photo/default.jpg') {
            return;
        }

        // Convierte a absoluta bajo /public
        $absolutePath = rtrim($projectDir, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        if ($this->filesystem->exists($absolutePath)) {
            $this->filesystem->remove($absolutePath);
        }
    }
}
