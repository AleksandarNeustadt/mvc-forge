{{-- Contact Messages Index Page --}}

@php
require_once ViewEngine::getViewPath() . '/helpers/crud-table.php';

global $router;
$lang = $router->lang ?? 'sr';

renderCrudTable([
    'app' => 'contact-messages',
    'createUrl' => '', // No create URL for contact messages
    'editUrl' => "/{$lang}/dashboard/contact-messages/{id}",
    'deleteUrl' => "/{$lang}/dashboard/contact-messages/{id}/delete",
    'enableLanguageFilter' => false,
    'enableSearch' => true,
    'enableSort' => true,
    'defaultSort' => 'created_at',
    'defaultOrder' => 'desc',
    'perPage' => 50,
    'columns' => [
        [
            'key' => 'id',
            'label' => 'ID',
            'sortable' => true
        ],
        [
            'key' => 'name',
            'label' => 'Korisnik',
            'sortable' => true,
            'render' => function($row) {
                $name = htmlspecialchars($row['name'] ?? 'N/A');
                $userId = !empty($row['user_id']) ? '<div class="text-xs text-slate-400">ID: ' . htmlspecialchars($row['user_id']) . '</div>' : '';
                return '<div class="text-sm font-medium text-white">' . $name . '</div>' . $userId;
            }
        ],
        [
            'key' => 'email',
            'label' => 'Email',
            'sortable' => true
        ],
        [
            'key' => 'subject',
            'label' => 'Predmet',
            'sortable' => true,
            'render' => function($row) {
                $subject = htmlspecialchars($row['subject'] ?? '');
                $message = mb_substr($row['message'] ?? '', 0, 60);
                $messagePreview = !empty($message) ? '<div class="text-xs text-slate-400 mt-1 max-w-xs truncate" title="' . htmlspecialchars($row['message'] ?? '') . '">' . htmlspecialchars($message) . (mb_strlen($row['message'] ?? '') > 60 ? '...' : '') . '</div>' : '';
                return '<div class="text-sm text-white max-w-xs truncate" title="' . htmlspecialchars($subject) . '">' . $subject . '</div>' . $messagePreview;
            }
        ],
        [
            'key' => 'status',
            'label' => 'Status',
            'sortable' => true,
            'render' => function($row) {
                $status = $row['status'] ?? 'unread';
                $statusClass = match($status) {
                    'unread' => 'bg-blue-900/50 text-blue-300',
                    'read' => 'bg-slate-700/50 text-slate-400',
                    'replied' => 'bg-green-900/50 text-green-300',
                    default => 'bg-slate-700/50 text-slate-400',
                };
                $statusLabel = match($status) {
                    'unread' => 'Nepročitano',
                    'read' => 'Pročitano',
                    'replied' => 'Odgovoreno',
                    default => ucfirst($status),
                };
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusClass . '">' . htmlspecialchars($statusLabel) . '</span>';
            }
        ],
        [
            'key' => 'created_at',
            'label' => 'Datum',
            'sortable' => true,
            'render' => function($row) {
                $createdAt = $row['created_at'] ?? null;
                if ($createdAt) {
                    $timestamp = is_int($createdAt) ? $createdAt : strtotime($createdAt);
                    return date('d.m.Y H:i', $timestamp);
                }
                return 'N/A';
            }
        ]
    ]
]);
@endphp
