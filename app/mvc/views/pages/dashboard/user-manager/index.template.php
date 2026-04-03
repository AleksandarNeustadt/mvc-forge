{{-- User Manager Index --}}

@php
require_once ViewEngine::getViewPath() . '/helpers/crud-table.php';

global $router;
$lang = $router->lang ?? 'sr';

renderCrudTable([
    'app' => 'users',
    'createUrl' => "/{$lang}/dashboard/users/create",
    'editUrl' => "/{$lang}/dashboard/users/{id}/edit",
    'deleteUrl' => "/{$lang}/dashboard/users/{id}/delete",
    'enableLanguageFilter' => false, // Users don't have language filter
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
            'key' => 'avatar',
            'label' => 'Avatar',
            'sortable' => false,
            'render' => 'renderUserAvatarColumn'
        ],
        [
            'key' => 'first_name',
            'label' => 'Name',
            'sortable' => false,
            'render' => 'renderUserNameColumn'
        ],
        [
            'key' => 'email',
            'label' => 'Email',
            'sortable' => true
        ],
        [
            'key' => 'username',
            'label' => 'Username',
            'sortable' => true
        ],
        [
            'key' => 'newsletter',
            'label' => 'Newsletter',
            'sortable' => true,
            'render' => 'renderNewsletterColumn'
        ],
        [
            'key' => 'status',
            'label' => 'Status',
            'sortable' => true,
            'render' => 'renderUserStatusColumn'
        ],
        [
            'key' => 'created_at',
            'label' => 'Created',
            'sortable' => true,
            'render' => 'renderUserCreatedColumn'
        ]
    ],
    'customActions' => [
        'render' => 'renderUserCustomActions'
    ]
]);
@endphp

<script nonce="{{ csp_nonce() }}">
// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Define render functions for this specific table
window.crudTableRenderFunctions = window.crudTableRenderFunctions || {};
window.crudTableRenderFunctions['crud-table-users'] = {
    renderUserAvatarColumn: function(row) {
        const avatar = row.avatar;
        const username = row.username || 'U';
        const initial = username.charAt(0).toUpperCase();
        
        if (avatar) {
            return `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(username)}" class="w-10 h-10 rounded-full object-cover">`;
        } else {
            return `<div class="w-10 h-10 rounded-full bg-theme-primary/20 border border-theme-primary/50 flex items-center justify-center">
                <span class="text-theme-primary font-semibold text-sm">${escapeHtml(initial)}</span>
            </div>`;
        }
    },
    renderUserNameColumn: function(row) {
        const firstName = row.first_name || '';
        const lastName = row.last_name || '';
        const fullName = (firstName + ' ' + lastName).trim();
        const displayName = fullName || row.username || row.email || 'Unknown';
        return `<div class="text-sm font-medium text-white">${escapeHtml(displayName)}</div>`;
    },
    renderNewsletterColumn: function(row) {
        const newsletter = row.newsletter;
        if (newsletter) {
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-300">Yes</span>`;
        } else {
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-700/50 text-slate-400">No</span>`;
        }
    },
    renderUserStatusColumn: function(row) {
        const status = row.status || 'pending';
        const statusClasses = {
            'active': 'bg-green-900/50 text-green-300',
            'pending': 'bg-yellow-900/50 text-yellow-300',
            'banned': 'bg-red-900/50 text-red-300'
        };
        const statusClass = statusClasses[status] || 'bg-slate-700/50 text-slate-400';
        // Capitalize first letter (JavaScript equivalent of PHP ucfirst)
        const statusText = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">${escapeHtml(statusText)}</span>`;
    },
    renderUserCreatedColumn: function(row) {
        if (!row.created_at) {
            return '<span class="text-slate-500">N/A</span>';
        }
        
        let timestamp;
        if (typeof row.created_at === 'number') {
            timestamp = row.created_at;
        } else if (typeof row.created_at === 'string') {
            const date = new Date(row.created_at);
            timestamp = !isNaN(date.getTime()) ? Math.floor(date.getTime() / 1000) : null;
        } else {
            return '<span class="text-slate-500">N/A</span>';
        }
        
        if (!timestamp) {
            return '<span class="text-slate-500">N/A</span>';
        }
        
        const date = new Date(timestamp * 1000);
        const formattedDate = `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.${date.getFullYear()}`;
        return `<span class="text-sm text-slate-400">${formattedDate}</span>`;
    },
    renderUserCustomActions: function(row) {
        const lang = '{{ $lang }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const userId = row.id;
        @php
        $currentUserId = $_SESSION['user_id'] ?? 0;
        @endphp
        const isCurrentUser = userId == {{ $currentUserId }};
        const status = row.status || 'pending';
        
        let actionsHtml = '';
        
        // View button
        actionsHtml += `
            <a href="/${lang}/dashboard/users/${userId}"
               class="text-blue-400 hover:text-blue-300 transition-colors" 
               title="View User">
                <ion-icon name="eye-outline" class="text-xl"></ion-icon>
            </a>
        `;
        
        // Edit button
        actionsHtml += `
            <a href="/${lang}/dashboard/users/${userId}/edit"
               class="text-theme-primary hover:text-theme-primary/80 transition-colors" 
               title="Edit User">
                <ion-icon name="create-outline" class="text-xl"></ion-icon>
            </a>
        `;
        
        if (!isCurrentUser) {
            // Approve button (only if pending)
            if (status === 'pending') {
                actionsHtml += `
                    <form method="POST" action="/${lang}/dashboard/users/${userId}/approve" class="inline user-action-form" data-action="approve" data-message="Are you sure you want to approve this user?">
                        <input type="hidden" name="_csrf_token" value="${csrfToken}">
                        <button type="submit" class="text-green-400 hover:text-green-300 transition-colors" title="Approve User">
                            <ion-icon name="checkmark-circle-outline" class="text-xl"></ion-icon>
                        </button>
                    </form>
                `;
            }
            
            // Ban/Unban button
            if (status === 'banned') {
                actionsHtml += `
                    <form method="POST" action="/${lang}/dashboard/users/${userId}/unban" class="inline user-action-form" data-action="unban" data-message="Are you sure you want to unban this user?">
                        <input type="hidden" name="_csrf_token" value="${csrfToken}">
                        <button type="submit" class="text-green-400 hover:text-green-300 transition-colors" title="Unban User">
                            <ion-icon name="checkmark-circle-outline" class="text-xl"></ion-icon>
                        </button>
                    </form>
                `;
            } else {
                actionsHtml += `
                    <form method="POST" action="/${lang}/dashboard/users/${userId}/ban" class="inline user-action-form" data-action="ban" data-message="Are you sure you want to ban this user?">
                        <input type="hidden" name="_csrf_token" value="${csrfToken}">
                        <button type="submit" class="text-orange-400 hover:text-orange-300 transition-colors" title="Ban User">
                            <ion-icon name="ban-outline" class="text-xl"></ion-icon>
                        </button>
                    </form>
                `;
            }
            
            // Delete button
            actionsHtml += `
                <form method="POST" action="/${lang}/dashboard/users/${userId}/delete" class="inline user-action-form" data-action="delete" data-message="Are you sure you want to delete this user? This action cannot be undone.">
                    <input type="hidden" name="_csrf_token" value="${csrfToken}">
                    <button type="submit" class="text-red-400 hover:text-red-300 transition-colors" title="Delete User">
                        <ion-icon name="trash-outline" class="text-xl"></ion-icon>
                    </button>
                </form>
            `;
        } else {
            actionsHtml += `<span class="text-slate-500 text-xs" title="Cannot perform actions on your own account">—</span>`;
        }
        
        return `<div class="flex items-center justify-end gap-2">${actionsHtml}</div>`;
    }
};

// Bind form submission handlers (CSP-compliant with event delegation)
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.classList.contains('user-action-form')) {
            const message = form.getAttribute('data-message');
            if (message && !confirm(message)) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
