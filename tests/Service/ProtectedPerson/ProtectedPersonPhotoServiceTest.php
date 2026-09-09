<?php

namespace App\Tests\Service\ProtectedPerson;

use App\Entity\ProtectedPerson;
use App\Service\ProtectedPerson\ProtectedPersonPhotoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProtectedPersonPhotoServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ProtectedPersonPhotoService $photoService;
    private string $projectDir;

    protected function setUp(): void
    {
        // Use a stub by default for tests that do not need to verify EntityManager calls.
        $this->em = $this->createStub(EntityManagerInterface::class);

        // Create a unique temporary project directory for each test.
        $this->projectDir = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'protegeo-photo-test-'
            . bin2hex(random_bytes(4));

        // Create the temporary upload directory used by the photo service.
        mkdir(
            $this->getUploadDirectory(),
            0775,
            true
        );

        $this->photoService = new ProtectedPersonPhotoService(
            $this->em,
            $this->projectDir
        );
    }

    protected function tearDown(): void
    {
        // Remove all temporary files and directories created during the test.
        $this->removeDirectory($this->projectDir);
    }

    /**
     * Checks that a valid photo can be uploaded successfully.
     */
    public function testUploadPhotoSuccessfully(): void
    {
        $protectedPerson = new ProtectedPerson();
        $photo = $this->createPngFile();

        $photoService = $this->createPhotoServiceWithFlushExpectation();

        $filename = $photoService->upload(
            $protectedPerson,
            $photo
        );

        $photoPath = $this->getUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $filename;

        $this->assertSame(
            $filename,
            $protectedPerson->getPhotoUrl()
        );

        $this->assertFileExists($photoPath);

        $this->assertNotNull(
            $protectedPerson->getUpdatedAt()
        );
    }

    /**
     * Checks that uploading a new photo removes the previous one.
     */
    public function testUploadReplacesExistingPhoto(): void
    {
        $protectedPerson = new ProtectedPerson();

        $oldFilename = 'old-photo.png';

        $oldPhotoPath = $this->getUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $oldFilename;

        file_put_contents(
            $oldPhotoPath,
            $this->getPngContent()
        );

        $protectedPerson->setPhotoUrl($oldFilename);

        $photo = $this->createPngFile();

        $photoService = $this->createPhotoServiceWithFlushExpectation();

        $newFilename = $photoService->upload(
            $protectedPerson,
            $photo
        );

        $newPhotoPath = $this->getUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $newFilename;

        $this->assertFileDoesNotExist($oldPhotoPath);
        $this->assertFileExists($newPhotoPath);

        $this->assertSame(
            $newFilename,
            $protectedPerson->getPhotoUrl()
        );
    }

    /**
     * Checks that unsupported file types are rejected.
     */
    public function testUploadFailsWithInvalidFileType(): void
    {
        $protectedPerson = new ProtectedPerson();

        $path = tempnam(
            sys_get_temp_dir(),
            'protegeo-invalid-'
        );

        file_put_contents(
            $path,
            'This is not an image.'
        );

        $photo = new UploadedFile(
            $path,
            'document.txt',
            'text/plain',
            null,
            true
        );

        $this->expectException(
            \InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Le format de la photo doit être JPEG, PNG ou WebP.'
        );

        $this->photoService->upload(
            $protectedPerson,
            $photo
        );
    }

    /**
     * Checks that an existing profile photo can be deleted.
     */
    public function testDeletePhotoSuccessfully(): void
    {
        $protectedPerson = new ProtectedPerson();

        $filename = 'profile-photo.png';

        $photoPath = $this->getUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $filename;

        file_put_contents(
            $photoPath,
            $this->getPngContent()
        );

        $protectedPerson->setPhotoUrl($filename);

        $photoService = $this->createPhotoServiceWithFlushExpectation();

        $photoService->delete(
            $protectedPerson
        );

        $this->assertFileDoesNotExist($photoPath);

        $this->assertNull(
            $protectedPerson->getPhotoUrl()
        );

        $this->assertNotNull(
            $protectedPerson->getUpdatedAt()
        );
    }

    /**
     * Checks that the service returns the full path of an existing photo.
     */
    public function testGetPhotoPathReturnsExistingPhoto(): void
    {
        $protectedPerson = new ProtectedPerson();

        $filename = 'profile-photo.png';

        $photoPath = $this->getUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $filename;

        file_put_contents(
            $photoPath,
            $this->getPngContent()
        );

        $protectedPerson->setPhotoUrl($filename);

        $result = $this->photoService->getPhotoPath(
            $protectedPerson
        );

        $this->assertSame(
            $photoPath,
            $result
        );
    }

    /**
     * Checks that the service fails when no profile photo exists.
     */
    public function testGetPhotoPathFailsWhenNoPhotoExists(): void
    {
        $protectedPerson = new ProtectedPerson();

        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Aucune photo de profil n’a été trouvée.'
        );

        $this->photoService->getPhotoPath(
            $protectedPerson
        );
    }

    /**
     * Returns the temporary protected-person upload directory.
     */
    private function getUploadDirectory(): string
    {
        return $this->projectDir
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'protected-person';
    }

    /**
     * Creates a valid temporary PNG file for upload tests.
     */
    private function createPngFile(): UploadedFile
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'protegeo-photo-'
        );

        file_put_contents(
            $path,
            $this->getPngContent()
        );

        return new UploadedFile(
            $path,
            'profile.png',
            'image/png',
            null,
            true
        );
    }

    /**
     * Returns the content of a minimal valid PNG image.
     */
    private function getPngContent(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );
    }

    /**
     * Creates the photo service with an EntityManager mock expecting one flush.
     */
    private function createPhotoServiceWithFlushExpectation(): ProtectedPersonPhotoService
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em
            ->expects($this->once())
            ->method('flush');

        return new ProtectedPersonPhotoService(
            $em,
            $this->projectDir
        );
    }

    /**
     * Recursively removes a directory and all its contents.
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}