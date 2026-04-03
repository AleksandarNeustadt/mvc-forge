<?php

namespace App\Models;


use App\Core\database\DatabaseBuilder;use BadMethodCallException;
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
 * AuditLog Model
 * 
 * Tracks all changes to important data (users, roles, permissions, etc.)
 */
class AuditLog extends Model
{
    protected string $table = 'audit_logs';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'old_values',
        'new_values',
        'ip',
        'user_agent',
        'created_at'
    ];
    
    protected array $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'created_at' => 'datetime'
    ];

    /**
     * Create audit log entry
     */
    public static function log(string $action, string $model, ?int $modelId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            // Check if audit_logs table exists (backward compatibility)
            if (!class_exists('DatabaseBuilder')) {
                require_once __DIR__ . '/../../core/database/DatabaseBuilder.php';
            }
            
            $tables = DatabaseBuilder::getTables();
            if (!in_array('audit_logs', $tables)) {
                // Table doesn't exist yet, skip logging
                return;
            }
            
            $log = new self();
            $log->user_id = $_SESSION['user_id'] ?? null;
            $log->action = $action;
            $log->model = $model;
            $log->model_id = $modelId;
            $log->old_values = $oldValues;
            $log->new_values = $newValues;
            $log->ip = self::getClientIp();
            $log->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $log->save();
        } catch (Exception $e) {
            // Silently fail if audit log can't be created
            // Don't log to avoid infinite loop
        }
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $ip = $_SERVER[$header] ?? null;
            if ($ip) {
                $ip = trim(explode(',', $ip)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}


if (!\class_exists('AuditLog', false) && !\interface_exists('AuditLog', false) && !\trait_exists('AuditLog', false)) {
    \class_alias(__NAMESPACE__ . '\\AuditLog', 'AuditLog');
}
