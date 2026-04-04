<?php
// Ensure $user is an array
if (is_object($user)) {
    $user = (array) $user;
}
if (is_object($apiToken ?? null)) {
    $apiToken = (array) $apiToken;
}
if (!is_array($apiToken ?? null)) {
    $apiToken = [];
}
?>
<div class="p-8">
    
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
        </div>
        <div class="flex items-center gap-3">
            <?php global $router; $lang = $router->lang ?? 'sr'; ?>
            <a href="/<?= $lang ?>/dashboard/users/<?= $user['id'] ?? 0 ?>/edit" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="create-outline"></ion-icon>
                Edit User
            </a>
            <a href="/<?= $lang ?>/dashboard/users" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Back to Users
            </a>
        </div>
    </div>

    <!-- User Details Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6 mb-6">
        <div class="flex items-start gap-6">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" 
                         alt="<?= e($user['username'] ?? '') ?>" 
                         class="w-32 h-32 rounded-full object-cover border-4 border-slate-700">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-slate-700/50 border-4 border-slate-600 flex items-center justify-center">
                        <span class="text-theme-primary font-bold text-4xl">
                            <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- User Info -->
            <div class="flex-1">
                <div class="mb-4">
                    <h2 class="text-3xl font-bold text-white mb-2">
                        <?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: e($user['username'] ?? 'Unknown User') ?>
                    </h2>
                    <p class="text-slate-400">@<?= e($user['username'] ?? '') ?></p>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Email</p>
                        <p class="text-white"><?= e($user['email'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">User ID</p>
                        <p class="text-white">#<?= e($user['id'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Status</p>
                        <?php
                        $status = $user['status'] ?? 'pending';
                        $statusClass = match($status) {
                            'active' => 'bg-green-900/50 text-green-300',
                            'pending' => 'bg-yellow-900/50 text-yellow-300',
                            'banned' => 'bg-red-900/50 text-red-300',
                            default => 'bg-slate-700/50 text-slate-400',
                        };
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                            <?= e(ucfirst($status)) ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Newsletter</p>
                        <p class="text-white">
                            <?= ($user['newsletter'] ?? false) ? 'Subscribed' : 'Not subscribed' ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Created</p>
                        <p class="text-white">
                            <?= !empty($user['created_at']) ? date('Y-m-d H:i', is_int($user['created_at']) ? $user['created_at'] : strtotime($user['created_at'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Last Login</p>
                        <p class="text-white">
                            <?= !empty($user['last_login_at']) ? date('Y-m-d H:i', is_int($user['last_login_at']) ? $user['last_login_at'] : strtotime($user['last_login_at'])) : 'Never' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Token Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6 mb-6">
        <h3 class="text-xl font-bold text-white mb-2">API Token</h3>
        <p class="text-slate-400 mb-4">Token za API upisivanje podataka u sistem. Koristi ga kao <span class="text-slate-200">Authorization: Bearer</span> header.</p>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <input
                id="dashboardApiToken"
                type="text"
                readonly
                value="<?= e($apiToken['token'] ?? '') ?>"
                class="flex-1 w-full bg-slate-900/70 border border-slate-700 rounded-lg px-4 py-3 text-sm text-slate-100 font-mono"
            >
            <button
                type="button"
                onclick="navigator.clipboard.writeText(document.getElementById('dashboardApiToken').value)"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors"
            >
                <ion-icon name="copy-outline"></ion-icon>
                Copy
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-slate-400 mb-1">Token Name</p>
                <p class="text-white"><?= e($apiToken['name'] ?? 'Dashboard API Token') ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-400 mb-1">Expires At</p>
                <p class="text-white">
                    <?= !empty($apiToken['expires_at']) ? date('Y-m-d H:i', is_int($apiToken['expires_at']) ? $apiToken['expires_at'] : strtotime((string) $apiToken['expires_at'])) : 'Never' ?>
                </p>
            </div>
            <div>
                <p class="text-sm text-slate-400 mb-1">Last Used</p>
                <p class="text-white">
                    <?= !empty($apiToken['last_used_at']) ? date('Y-m-d H:i', is_int($apiToken['last_used_at']) ? $apiToken['last_used_at'] : strtotime((string) $apiToken['last_used_at'])) : 'Never' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Actions Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <h3 class="text-xl font-bold text-white mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <?php if ((int)($user['id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                <?php global $router; $lang = $router->lang ?? 'sr'; ?>
                <?php if (($user['status'] ?? '') === 'banned'): ?>
                    <form method="POST" action="/<?= $lang ?>/dashboard/users/<?= (int)$user['id'] ?>/unban" class="inline">
                        <?php echo CSRF::field(); ?>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                            Unban User
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="/<?= $lang ?>/dashboard/users/<?= (int)$user['id'] ?>/ban" class="inline">
                        <?php echo CSRF::field(); ?>
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors user-action-btn"
                                data-action="ban"
                                data-message="Are you sure you want to ban this user?">
                            <ion-icon name="ban-outline"></ion-icon>
                            Ban User
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (($user['status'] ?? '') === 'pending'): ?>
                    <form method="POST" action="/<?= $lang ?>/dashboard/users/<?= (int)$user['id'] ?>/approve" class="inline">
                        <?php echo CSRF::field(); ?>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="checkmark-outline"></ion-icon>
                            Approve User
                        </button>
                    </form>
                <?php endif; ?>

                <form method="POST" action="/<?= $lang ?>/dashboard/users/<?= (int)$user['id'] ?>/delete" class="inline">
                    <?php echo CSRF::field(); ?>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors user-action-btn"
                            data-action="delete"
                            data-message="Are you sure you want to delete this user? This action cannot be undone.">
                        <ion-icon name="trash-outline"></ion-icon>
                        Delete User
                    </button>
                </form>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.user-action-btn');
    buttons.forEach(function(button) {
        const form = button.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const message = button.getAttribute('data-message');
                if (message && !confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
});
</script>
            <?php else: ?>
                <p class="text-slate-400 italic">You cannot perform actions on your own account</p>
            <?php endif; ?>
        </div>
    </div>

</div>
