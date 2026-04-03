{{-- Show User --}}

@php
// Ensure $user is an array
if (is_object($user)) {
    $user = (array) $user;
}
global $router;
$lang = $router->lang ?? 'sr';
$currentUserId = $_SESSION['user_id'] ?? 0;
$userId = (int)($user['id'] ?? 0);
$isCurrentUser = ($userId == $currentUserId);
@endphp

<div class="p-8">
    {{-- Page Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
        </div>
        <div class="flex items-center gap-3">
            <a href="/{{ $lang }}/dashboard/users/{{ $userId }}/edit" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="create-outline"></ion-icon>
                Edit User
            </a>
            <a href="/{{ $lang }}/dashboard/users" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Back to Users
            </a>
        </div>
    </div>

    {{-- User Details Card --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6 mb-6">
        <div class="flex items-start gap-6">
            {{-- Avatar --}}
            <div class="flex-shrink-0">
                @if (!empty($user['avatar']))
                    <img src="{{ e($user['avatar']) }}" 
                         alt="{{ e($user['username'] ?? '') }}" 
                         class="w-32 h-32 rounded-full object-cover border-4 border-slate-700">
                @else
                    <div class="w-32 h-32 rounded-full bg-slate-700/50 border-4 border-slate-600 flex items-center justify-center">
                        <span class="text-theme-primary font-bold text-4xl">
                            {{ strtoupper(substr($user['username'] ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- User Info --}}
            <div class="flex-1">
                <div class="mb-4">
                    <h2 class="text-3xl font-bold text-white mb-2">
                        {{ e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: e($user['username'] ?? 'Unknown User') }}
                    </h2>
                    <p class="text-slate-400">@{{ e($user['username'] ?? '') }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Email</p>
                        <p class="text-white">{{ e($user['email'] ?? 'N/A') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">User ID</p>
                        <p class="text-white">#{{ e($user['id'] ?? 'N/A') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Status</p>
                        @php
                        $status = $user['status'] ?? 'pending';
                        $statusClass = match($status) {
                            'active' => 'bg-green-900/50 text-green-300',
                            'pending' => 'bg-yellow-900/50 text-yellow-300',
                            'banned' => 'bg-red-900/50 text-red-300',
                            default => 'bg-slate-700/50 text-slate-400',
                        };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                            {{ e(ucfirst($status)) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Newsletter</p>
                        <p class="text-white">
                            {{ ($user['newsletter'] ?? false) ? 'Subscribed' : 'Not subscribed' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Created</p>
                        <p class="text-white">
                            @if (!empty($user['created_at']))
                                {{ date('Y-m-d H:i', is_int($user['created_at']) ? $user['created_at'] : strtotime($user['created_at'])) }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Last Login</p>
                        <p class="text-white">
                            @if (!empty($user['last_login_at']))
                                {{ date('Y-m-d H:i', is_int($user['last_login_at']) ? $user['last_login_at'] : strtotime($user['last_login_at'])) }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions Card --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <h3 class="text-xl font-bold text-white mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            @if (!$isCurrentUser)
                @if (($user['status'] ?? '') === 'banned')
                    <form method="POST" action="/{{ $lang }}/dashboard/users/{{ $userId }}/unban" class="inline user-action-form" data-action="unban" data-message="Are you sure you want to unban this user?">
                        {{ CSRF::field() }}
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                            Unban User
                        </button>
                    </form>
                @else
                    <form method="POST" action="/{{ $lang }}/dashboard/users/{{ $userId }}/ban" class="inline user-action-form" data-action="ban" data-message="Are you sure you want to ban this user?">
                        {{ CSRF::field() }}
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="ban-outline"></ion-icon>
                            Ban User
                        </button>
                    </form>
                @endif

                @if (($user['status'] ?? '') === 'pending')
                    <form method="POST" action="/{{ $lang }}/dashboard/users/{{ $userId }}/approve" class="inline user-action-form" data-action="approve" data-message="Are you sure you want to approve this user?">
                        {{ CSRF::field() }}
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="checkmark-outline"></ion-icon>
                            Approve User
                        </button>
                    </form>
                @endif

                <form method="POST" action="/{{ $lang }}/dashboard/users/{{ $userId }}/delete" class="inline user-action-form" data-action="delete" data-message="Are you sure you want to delete this user? This action cannot be undone.">
                    {{ CSRF::field() }}
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                        <ion-icon name="trash-outline"></ion-icon>
                        Delete User
                    </button>
                </form>
            @else
                <p class="text-slate-400 italic">You cannot perform actions on your own account</p>
            @endif
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
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
