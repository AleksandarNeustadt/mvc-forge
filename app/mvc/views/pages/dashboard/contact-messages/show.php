<div class="p-8">
    
    <!-- Back Button -->
    <div class="mb-6">
        <?php global $router; $lang = $router->lang ?? 'sr'; ?>
        <a href="/<?= $lang ?>/dashboard/contact-messages" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            <span>Nazad na listu poruka</span>
        </a>
    </div>

    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Message Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-8 mb-6">
        <!-- Header -->
        <div class="flex items-start justify-between mb-6 pb-6 border-b border-slate-700/50">
            <div>
                <h2 class="text-2xl font-bold text-white mb-2"><?= e($message['subject'] ?? '') ?></h2>
                <div class="flex items-center gap-4 text-sm text-slate-400">
                    <div class="flex items-center gap-2">
                        <ion-icon name="person-outline"></ion-icon>
                        <span><?= e($message['name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <ion-icon name="mail-outline"></ion-icon>
                        <span><?= e($message['email'] ?? 'N/A') ?></span>
                    </div>
                    <?php if (!empty($message['user_id'])): ?>
                        <div class="flex items-center gap-2">
                            <ion-icon name="person-circle-outline"></ion-icon>
                            <span>Korisnik ID: <?= e($message['user_id']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <?php
                $status = $message['status'] ?? 'unread';
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
                ?>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium <?= $statusClass ?>">
                    <?= e($statusLabel) ?>
                </span>
            </div>
        </div>

        <!-- Message Content -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white mb-3">Poruka:</h3>
            <div class="bg-slate-900/50 rounded-lg p-6 text-slate-300 whitespace-pre-wrap">
                <?= e($message['message'] ?? '') ?>
            </div>
        </div>

        <!-- Metadata -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6 border-t border-slate-700/50">
            <div>
                <p class="text-sm text-slate-400 mb-1">Datum slanja:</p>
                <p class="text-white">
                    <?php
                    $createdAt = $message['created_at'] ?? null;
                    if ($createdAt) {
                        $timestamp = is_int($createdAt) ? $createdAt : strtotime($createdAt);
                        echo date('d.m.Y H:i:s', $timestamp);
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </p>
            </div>
            <?php if (!empty($message['read_at'])): ?>
                <div>
                    <p class="text-sm text-slate-400 mb-1">Pročitano:</p>
                    <p class="text-white">
                        <?php
                        $readAt = $message['read_at'];
                        $timestamp = is_int($readAt) ? $readAt : strtotime($readAt);
                        echo date('d.m.Y H:i:s', $timestamp);
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            <?php if (!empty($message['replied_at'])): ?>
                <div>
                    <p class="text-sm text-slate-400 mb-1">Odgovoreno:</p>
                    <p class="text-white">
                        <?php
                        $repliedAt = $message['replied_at'];
                        $timestamp = is_int($repliedAt) ? $repliedAt : strtotime($repliedAt);
                        echo date('d.m.Y H:i:s', $timestamp);
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            <?php if (!empty($message['ip_address'])): ?>
                <div>
                    <p class="text-sm text-slate-400 mb-1">IP Adresa:</p>
                    <p class="text-white font-mono text-sm"><?= e($message['ip_address']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-4">
        <?php if (!empty($message['id'])): ?>
            <?php $messageId = (int)$message['id']; ?>
            
            <?php if (($message['status'] ?? 'unread') === 'unread'): ?>
                <!-- Mark as Read -->
                <form method="POST" action="/<?= $lang ?>/dashboard/contact-messages/<?= $messageId ?>/read"
                      class="inline"
                      data-action="read">
                    <?php echo CSRF::field(); ?>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <ion-icon name="checkmark-circle-outline"></ion-icon>
                        Označi kao pročitano
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Mark as Replied -->
            <?php if (($message['status'] ?? 'unread') !== 'replied'): ?>
                <form method="POST" action="/<?= $lang ?>/dashboard/contact-messages/<?= $messageId ?>/replied"
                      class="inline"
                      data-action="replied"
                      data-message="Označiti poruku kao odgovorenu?">
                    <?php echo CSRF::field(); ?>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <ion-icon name="mail-outline"></ion-icon>
                        Označi kao odgovoreno
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Delete Message -->
            <form method="POST" action="/<?= $lang ?>/dashboard/contact-messages/<?= $messageId ?>/delete"
                  class="inline"
                  data-action="delete"
                  data-message="Da li ste sigurni da želite da obrišete ovu poruku? Ova akcija se ne može poništiti.">
                <?php echo CSRF::field(); ?>
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                    <ion-icon name="trash-outline"></ion-icon>
                    Obriši poruku
                </button>
            </form>
        <?php endif; ?>
    </div>

</div>

<script nonce="<?= csp_nonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-action]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const message = form.getAttribute('data-message');
            if (message && !confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
});
</script>

