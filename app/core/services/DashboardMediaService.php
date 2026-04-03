<?php

namespace App\Core\services;

use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Service layer for dashboard blog media uploads and file serving.
 */
class DashboardMediaService
{
    private const BLOG_UPLOAD_DIR = 'storage/uploads/blog/';
    private const BLOG_IMAGE_MAX_SIZE = 5242880;
    private const BLOG_IMAGE_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private const BLOG_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function uploadTinyMceImage(?array $file): array
    {
        $validationError = $this->validateUploadedImage($file);
        if ($validationError !== null) {
            return ['ok' => false, 'error' => $validationError, 'status' => 400];
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $filename = uniqid('blog_', true) . '_' . time() . '.' . $extension;
        $uploadDir = dirname(__DIR__, 2) . '/' . self::BLOG_UPLOAD_DIR;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;
        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $destination)) {
            return ['ok' => false, 'error' => 'Failed to save uploaded file', 'status' => 500];
        }

        return [
            'ok' => true,
            'payload' => ['location' => '/storage/uploads/blog/' . $filename],
        ];
    }

    public function getServedBlogImage(string $filename): array
    {
        $safeFilename = basename($filename);
        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

        if (!in_array($extension, self::BLOG_IMAGE_EXTENSIONS, true)) {
            return ['ok' => false, 'error' => 'Invalid file type', 'status' => 403];
        }

        $filePath = dirname(__DIR__, 2) . '/' . self::BLOG_UPLOAD_DIR . $safeFilename;
        if (!file_exists($filePath) || !is_file($filePath)) {
            return ['ok' => false, 'error' => 'Image not found', 'status' => 404];
        }

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        return [
            'ok' => true,
            'path' => $filePath,
            'contentType' => $mimeTypes[$extension] ?? 'application/octet-stream',
            'contentLength' => filesize($filePath),
        ];
    }

    public function uploadFeaturedImage(string $fieldName = 'file'): array
    {
        try {
            $uploader = new UploadManager(self::BLOG_UPLOAD_DIR);
            $result = $uploader->upload($fieldName, [
                'maxSize' => self::BLOG_IMAGE_MAX_SIZE,
                'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'prefix' => 'featured_',
            ]);

            if (!$result) {
                $error = $uploader->getLastError();
                $errorMessage = str_contains($error, 'not writable')
                    ? 'Upload directory permissions issue. Please contact administrator or run: sudo chown -R www-data:www-data storage/uploads && sudo chmod -R 775 storage/uploads'
                    : $error;

                error_log('UploadFeaturedImage error: ' . $error);
                error_log('UploadFeaturedImage all errors: ' . json_encode($uploader->getErrors()));

                return [
                    'ok' => false,
                    'error' => $errorMessage,
                    'errors' => $uploader->getErrors(),
                    'status' => 400,
                ];
            }

            return [
                'ok' => true,
                'payload' => [
                    'url' => $result['url'],
                    'filename' => $result['filename'],
                ],
            ];
        } catch (Exception $e) {
            error_log('UploadFeaturedImage exception: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'Upload failed: ' . $e->getMessage(), 'status' => 500];
        }
    }

    private function validateUploadedImage(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'No file uploaded or upload error';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, (string) ($file['tmp_name'] ?? ''));
        finfo_close($finfo);

        if (!in_array($mimeType, self::BLOG_IMAGE_MIME_TYPES, true)) {
            return 'Invalid file type. Only images are allowed.';
        }

        if (($file['size'] ?? 0) > self::BLOG_IMAGE_MAX_SIZE) {
            return 'File too large. Maximum size is 5MB.';
        }

        return null;
    }
}


if (!\class_exists('DashboardMediaService', false) && !\interface_exists('DashboardMediaService', false) && !\trait_exists('DashboardMediaService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardMediaService', 'DashboardMediaService');
}
