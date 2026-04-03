<?php

namespace App\Core\services;


use App\Models\ContactMessage;use BadMethodCallException;
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
 * Service layer for dashboard contact message management.
 */
class DashboardContactMessageService
{
    public function buildMessageListData(int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $messagesData = ContactMessage::query()
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        $messages = [];
        $messageModel = new ContactMessage();
        foreach ($messagesData as $messageData) {
            $messages[] = $messageModel->newFromBuilder($messageData)->toArray();
        }

        $total = ContactMessage::query()->count();
        $totalPages = (int) ceil($total / $perPage);

        return [
            'messages' => $messages,
            'stats' => [
                'total' => $total,
                'unread' => ContactMessage::query()->where('status', 'unread')->count(),
                'replied' => ContactMessage::query()->where('status', 'replied')->count(),
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
            ],
        ];
    }

    public function markAsRead(ContactMessage $message): void
    {
        if ($message->isUnread()) {
            $message->markAsRead();
        }
    }

    public function buildShowData(ContactMessage $message): array
    {
        $this->markAsRead($message);

        return [
            'message' => $message->toArray(),
        ];
    }

    public function markAsReplied(ContactMessage $message): void
    {
        $message->markAsReplied();
    }

    public function deleteMessage(ContactMessage $message): void
    {
        $message->delete();
    }
}


if (!\class_exists('DashboardContactMessageService', false) && !\interface_exists('DashboardContactMessageService', false) && !\trait_exists('DashboardContactMessageService', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardContactMessageService', 'DashboardContactMessageService');
}
