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
 * Upload Manager - Secure File Upload Handler
 * 
 * Handles file uploads with security validation and proper storage
 * 
 * Usage:
 *   $uploader = new UploadManager('storage/uploads/blog/');
 *   $result = $uploader->upload('featured_image', [
 *       'maxSize' => 5 * 1024 * 1024, // 5MB
 *       'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
 *       'prefix' => 'blog_'
 *   ]);
 */
class UploadManager
{
    private string $baseDirectory;
    private array $errors = [];

    /**
     * Create new UploadManager instance
     * 
     * @param string $baseDirectory Base directory for uploads (relative to project root)
     */
    public function __construct(string $baseDirectory = 'storage/uploads/')
    {
        $this->baseDirectory = rtrim($baseDirectory, '/') . '/';
    }

    /**
     * Upload a file
     * 
     * @param string $fieldName Form field name
     * @param array $options Upload options
     * @return array|null Upload result with 'success', 'filename', 'path', 'url' or null on failure
     */
    public function upload(string $fieldName, array $options = []): ?array
    {
        $this->errors = [];

        // Check if file was uploaded
        if (!isset($_FILES[$fieldName])) {
            $this->errors[] = 'No file field found in upload request';
            error_log('UploadManager: $_FILES[' . $fieldName . '] not set. Available fields: ' . implode(', ', array_keys($_FILES)));
            return null;
        }
        
        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $errorCode = $_FILES[$fieldName]['error'];
            $errorMsg = $errorMessages[$errorCode] ?? 'Unknown upload error (code: ' . $errorCode . ')';
            $this->errors[] = 'Upload error: ' . $errorMsg;
            error_log('UploadManager: Upload error code ' . $errorCode . ': ' . $errorMsg);
            return null;
        }

        $file = $_FILES[$fieldName];

        // Default options
        $maxSize = $options['maxSize'] ?? 5 * 1024 * 1024; // 5MB default
        $allowedTypes = $options['allowedTypes'] ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $prefix = $options['prefix'] ?? 'upload_';
        $subdirectory = $options['subdirectory'] ?? '';

        // Validate file size
        if ($file['size'] > $maxSize) {
            $this->errors[] = 'File too large. Maximum size is ' . $this->formatBytes($maxSize);
            return null;
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $this->errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
            return null;
        }

        // Generate safe filename
        $extension = $this->getExtensionFromMime($mimeType, $file['name']);
        $filename = $prefix . uniqid('', true) . '_' . time() . '.' . $extension;

        // Build full path - use absolute path from project root
        $projectRoot = dirname(__DIR__, 2); // Go up from core/services to project root
        $uploadDir = $projectRoot . '/' . ltrim($this->baseDirectory, '/');
        if ($subdirectory) {
            $uploadDir .= rtrim($subdirectory, '/') . '/';
        }
        
        // Normalize path - resolve relative paths
        $uploadDir = rtrim($uploadDir, '/') . '/';
        $realPath = realpath(dirname($uploadDir));
        if ($realPath === false) {
            // Directory doesn't exist, try to create parent
            $parentDir = dirname($uploadDir);
            if (!is_dir($parentDir)) {
                if (!mkdir($parentDir, 0755, true)) {
                    $this->errors[] = 'Failed to create parent directory: ' . $parentDir;
                    error_log('UploadManager: Failed to create parent directory: ' . $parentDir);
                    return null;
                }
            }
            $realPath = realpath($parentDir);
        }
        
        $uploadDir = $realPath . '/' . basename(rtrim($uploadDir, '/')) . '/';

        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $this->errors[] = 'Failed to create upload directory: ' . $uploadDir;
                error_log('UploadManager: Failed to create directory: ' . $uploadDir . ' (parent: ' . dirname($uploadDir) . ')');
                return null;
            }
        }

        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            // Try to make it writable - use 775 to allow group write
            @chmod($uploadDir, 0775);
            // Also try to make parent directories writable
            $parentDir = dirname($uploadDir);
            if (is_dir($parentDir) && !is_writable($parentDir)) {
                @chmod($parentDir, 0775);
            }
            
            if (!is_writable($uploadDir)) {
                $perms = file_exists($uploadDir) ? substr(sprintf('%o', fileperms($uploadDir)), -4) : 'N/A';
                $owner = file_exists($uploadDir) ? (function_exists('posix_getpwuid') ? @posix_getpwuid(fileowner($uploadDir))['name'] : 'unknown') : 'unknown';
                $this->errors[] = 'Upload directory is not writable. Please run: chmod 775 ' . $uploadDir . ' && chown -R www-data:www-data ' . dirname($uploadDir);
                error_log('UploadManager: Directory not writable: ' . $uploadDir);
                error_log('UploadManager: Permissions: ' . $perms . ', Owner: ' . $owner);
                error_log('UploadManager: Current user: ' . get_current_user());
                if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                    $processUser = posix_getpwuid(posix_geteuid());
                    error_log('UploadManager: Process user: ' . ($processUser['name'] ?? 'unknown'));
                }
                return null;
            }
        }

        $destination = $uploadDir . $filename;

        // Check if we can write to the destination
        $testFile = $uploadDir . '.test_write_' . time();
        if (@file_put_contents($testFile, 'test') === false) {
            @unlink($testFile);
            $this->errors[] = 'Cannot write to upload directory: ' . $uploadDir;
            error_log('UploadManager: Cannot write test file to: ' . $uploadDir);
            return null;
        }
        @unlink($testFile);

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $error = error_get_last();
            $errorMsg = $error['message'] ?? 'Unknown error';
            
            // Check specific error conditions
            if (!file_exists($file['tmp_name'])) {
                $errorMsg = 'Uploaded file no longer exists (may have been moved or deleted)';
            } elseif (!is_readable($file['tmp_name'])) {
                $errorMsg = 'Cannot read uploaded file';
            } elseif (!is_writable($uploadDir)) {
                $errorMsg = 'Upload directory is not writable. Please check permissions.';
            } elseif (!is_writable(dirname($destination))) {
                $errorMsg = 'Cannot write to destination directory. Please check permissions.';
            } elseif (file_exists($destination)) {
                $errorMsg = 'Destination file already exists';
            } else {
                // Try to get more specific error
                $phpError = error_get_last();
                if ($phpError && isset($phpError['message'])) {
                    $errorMsg = $phpError['message'];
                } else {
                    $errorMsg = 'Failed to move uploaded file. Check directory permissions and disk space.';
                }
            }
            
            $this->errors[] = $errorMsg;
            error_log('UploadManager: Failed to move file from ' . $file['tmp_name'] . ' to ' . $destination);
            error_log('UploadManager: Error: ' . $errorMsg);
            error_log('UploadManager: Source exists: ' . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
            error_log('UploadManager: Source readable: ' . (is_readable($file['tmp_name']) ? 'yes' : 'no'));
            error_log('UploadManager: Destination dir writable: ' . (is_writable($uploadDir) ? 'yes' : 'no'));
            error_log('UploadManager: Destination exists: ' . (file_exists($destination) ? 'yes' : 'no'));
            error_log('UploadManager: Disk free space: ' . (disk_free_space($uploadDir) !== false ? $this->formatBytes(disk_free_space($uploadDir)) : 'unknown'));
            return null;
        }
        
        // Set file permissions
        @chmod($destination, 0644);

        // Generate public URL (without language prefix for static files)
        $urlPath = $this->baseDirectory;
        if ($subdirectory) {
            $urlPath .= rtrim($subdirectory, '/') . '/';
        }
        $urlPath .= $filename;
        // Remove 'storage/uploads/' prefix if present and create clean URL
        $cleanPath = str_replace('storage/uploads/', '', $urlPath);
        $publicUrl = "/storage/uploads/" . ltrim($cleanPath, '/');

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destination,
            'url' => $publicUrl,
            'size' => $file['size'],
            'mime' => $mimeType
        ];
    }

    /**
     * Get upload errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get last error message
     * 
     * @return string
     */
    public function getLastError(): string
    {
        return $this->errors[0] ?? 'Unknown error';
    }

    /**
     * Get extension from MIME type or filename
     */
    private function getExtensionFromMime(string $mime, string $originalName): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        if (isset($mimeMap[$mime])) {
            return $mimeMap[$mime];
        }

        // Fallback to original extension
        return strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'bin';
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}


if (!\class_exists('UploadManager', false) && !\interface_exists('UploadManager', false) && !\trait_exists('UploadManager', false)) {
    \class_alias(__NAMESPACE__ . '\\UploadManager', 'UploadManager');
}
