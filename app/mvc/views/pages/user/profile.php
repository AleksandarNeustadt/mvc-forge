<?php
// Ensure $profileUser is an array
if (is_object($profileUser)) {
    $profileUser = (array) $profileUser;
}
if (!is_array($profileUser)) {
    $profileUser = [];
}

// Get user roles
$userRoles = [];
try {
    if (!empty($profileUser['id'])) {
        $user = User::find($profileUser['id']);
        if ($user) {
            $roles = $user->roles();
            $userRoles = array_map(fn($r) => is_object($r) ? $r->toArray() : $r, $roles);
        }
    }
} catch (Exception $e) {
    // Roles unavailable, continue without them
}

global $router;
$lang = $router->lang ?? 'sr';
?>

<section class="relative w-full px-4 py-10 max-w-5xl mx-auto">
    
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-4xl font-bold text-white mb-2">My Profile</h1>
            <p class="text-slate-400">View and manage your account information</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/<?= $lang ?>/profile/edit" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="create-outline"></ion-icon>
                Edit Profile
            </a>
        </div>
    </div>

    <!-- User Details Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6 mb-6">
        <div class="flex flex-col md:flex-row items-start gap-6">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <?php if (!empty($profileUser['avatar'])): ?>
                    <img src="<?= e($profileUser['avatar']) ?>" 
                         alt="<?= e($profileUser['username'] ?? '') ?>" 
                         class="w-32 h-32 rounded-full object-cover border-4 border-slate-700">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-slate-700/50 border-4 border-slate-600 flex items-center justify-center">
                        <span class="text-theme-primary font-bold text-4xl">
                            <?= strtoupper(substr($profileUser['username'] ?? 'U', 0, 1)) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- User Info -->
            <div class="flex-1 w-full">
                <div class="mb-4">
                    <h2 class="text-3xl font-bold text-white mb-2">
                        <?= e(trim(($profileUser['first_name'] ?? '') . ' ' . ($profileUser['last_name'] ?? ''))) ?: e($profileUser['username'] ?? 'Unknown User') ?>
                    </h2>
                    <p class="text-slate-400">@<?= e($profileUser['username'] ?? '') ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Email</p>
                        <p class="text-white"><?= e($profileUser['email'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Status</p>
                        <?php
                        $status = $profileUser['status'] ?? 'pending';
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
                        <p class="text-sm text-slate-400 mb-1">Email Verified</p>
                        <p class="text-white">
                            <?= !empty($profileUser['email_verified_at']) ? 'Yes' : 'No' ?>
                            <?php if (empty($profileUser['email_verified_at'])): ?>
                                <span class="text-yellow-400 text-xs ml-2">(Unverified)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Newsletter</p>
                        <p class="text-white">
                            <?= ($profileUser['newsletter'] ?? false) ? 'Subscribed' : 'Not subscribed' ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Member Since</p>
                        <p class="text-white">
                            <?= !empty($profileUser['created_at']) ? date('F Y', is_int($profileUser['created_at']) ? $profileUser['created_at'] : strtotime($profileUser['created_at'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400 mb-1">Last Login</p>
                        <p class="text-white">
                            <?= !empty($profileUser['last_login_at']) ? date('Y-m-d H:i', is_int($profileUser['last_login_at']) ? $profileUser['last_login_at'] : strtotime($profileUser['last_login_at'])) : 'Never' ?>
                        </p>
                    </div>
                </div>

                <!-- Roles -->
                <?php if (!empty($userRoles)): ?>
                    <div class="mt-4 pt-4 border-t border-slate-700/50">
                        <p class="text-sm text-slate-400 mb-2">Roles</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($userRoles as $role): ?>
                                <?php
                                $roleArray = is_array($role) ? $role : (is_object($role) ? $role->toArray() : []);
                                $roleName = $roleArray['name'] ?? '';
                                $roleSlug = $roleArray['slug'] ?? '';
                                ?>
                                <?php if (!empty($roleName)): ?>
                                    <span class="inline-flex items-center px-3 py-1 bg-theme-primary/20 text-theme-primary text-sm font-medium rounded-full border border-theme-primary/30">
                                        <?= e($roleName) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Account Actions Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        <h3 class="text-xl font-bold text-white mb-4">Account Actions</h3>
        <div class="flex flex-wrap gap-3">
            <a href="/<?= $lang ?>/profile/edit" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="create-outline"></ion-icon>
                Edit Profile
            </a>
            <a href="/<?= $lang ?>/forgot-password" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="key-outline"></ion-icon>
                Change Password
            </a>
        </div>
    </div>

</section>

