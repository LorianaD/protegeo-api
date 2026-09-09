<?php

namespace App\Service\ProtectedPerson;

use App\Entity\ProtectedPerson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProtectedPersonPhotoService
{
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private string $projectDir,
    ) {}

    /**
     * Uploads and stores a protected person's profile photo.
     */
    public function upload(ProtectedPerson $protectedPerson, UploadedFile $photo): string
    {
        $this->validatePhoto($photo);

        $uploadDirectory = $this->getUploadDirectory();

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0775, true);
        }

        $this->deleteExistingFile($protectedPerson);

        $extension = $photo->guessExtension() ?? 'bin';
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $photo->move(
            $uploadDirectory,
            $filename
        );

        $protectedPerson->setPhotoUrl($filename);
        $protectedPerson->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $filename;
    }

    /**
     * Deletes the protected person's profile photo.
     */
    public function delete(ProtectedPerson $protectedPerson): void
    {
        $this->deleteExistingFile($protectedPerson);

        $protectedPerson->setPhotoUrl(null);
        $protectedPerson->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();
    }

    /**
     * Returns the absolute path of the protected person's profile photo.
     */
    public function getPhotoPath(ProtectedPerson $protectedPerson): string
    {
        $filename = $protectedPerson->getPhotoUrl();

        if (!$filename) {
            throw new \RuntimeException(
                'Aucune photo de profil n’a été trouvée.'
            );
        }

        $path = $this->getUploadDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($path)) {
            throw new \RuntimeException(
                'Le fichier de la photo de profil est introuvable.'
            );
        }

        return $path;
    }

    /**
     * Validates the uploaded photo.
     */
    private function validatePhoto(UploadedFile $photo): void
    {
        if (!$photo->isValid()) {
            throw new \InvalidArgumentException(
                'Le fichier envoyé est invalide.'
            );
        }

        if ($photo->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                'La photo ne doit pas dépasser 2 Mo.'
            );
        }

        if (!in_array($photo->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Le format de la photo doit être JPEG, PNG ou WebP.'
            );
        }
    }

    /**
     * Deletes the currently stored photo file when it exists.
     */
    private function deleteExistingFile(ProtectedPerson $protectedPerson): void
    {
        $filename = $protectedPerson->getPhotoUrl();

        if (!$filename) {
            return;
        }

        $path = $this->getUploadDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Returns the protected-person upload directory.
     */
    private function getUploadDirectory(): string
    {
        return $this->projectDir
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'protected-person';
    }
}