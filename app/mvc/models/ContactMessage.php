<?php

namespace App\Models;

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
 * Contact Message Model
 * 
 * Handles contact form submissions
 */
class ContactMessage extends Model
{
    protected string $table = 'contact_messages';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'ip_address',
        'user_agent',
        'status',
        'read_at',
        'replied_at'
    ];

    protected array $casts = [
        'user_id' => 'int',
        'status' => 'string',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get user who sent the message
     */
    public function user(): ?User
    {
        if (empty($this->user_id)) {
            return null;
        }
        
        return User::find($this->user_id);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): bool
    {
        $this->status = 'read';
        $this->read_at = time();
        return $this->save();
    }

    /**
     * Mark message as replied
     */
    public function markAsReplied(): bool
    {
        $this->status = 'replied';
        $this->replied_at = time();
        return $this->save();
    }

    /**
     * Check if message is unread
     */
    public function isUnread(): bool
    {
        return ($this->status ?? 'unread') === 'unread';
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return match($this->status ?? 'unread') {
            'unread' => 'bg-blue-900/50 text-blue-300',
            'read' => 'bg-slate-700/50 text-slate-400',
            'replied' => 'bg-green-900/50 text-green-300',
            default => 'bg-slate-700/50 text-slate-400',
        };
    }
}


if (!\class_exists('ContactMessage', false) && !\interface_exists('ContactMessage', false) && !\trait_exists('ContactMessage', false)) {
    \class_alias(__NAMESPACE__ . '\\ContactMessage', 'ContactMessage');
}
