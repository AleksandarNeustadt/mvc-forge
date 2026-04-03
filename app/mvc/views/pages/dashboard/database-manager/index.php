<div class="p-8">
    
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Database Info Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6 mb-8">
        <h2 class="text-xl font-semibold text-white mb-4">Database Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <span class="text-slate-400 text-sm">Driver</span>
                <p class="text-white font-medium"><?= e($dbInfo['driver'] ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-400 text-sm">Database</span>
                <p class="text-white font-medium"><?= e($dbInfo['database'] ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-400 text-sm">Host</span>
                <p class="text-white font-medium"><?= e($dbInfo['host'] ?? 'N/A') ?></p>
            </div>
            <div>
                <span class="text-slate-400 text-sm">Tables</span>
                <p class="text-white font-medium"><?= e($dbInfo['table_count'] ?? 0) ?></p>
            </div>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-white">Tables</h2>
        <a href="<?= route('dashboard.table.create') ?>" 
           class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/80 text-white font-medium rounded-lg transition-colors">
            <ion-icon name="add-outline"></ion-icon>
            Create Table
        </a>
    </div>

    <!-- Tables List -->
    <?php if (empty($tables)): ?>
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-12 text-center">
            <ion-icon name="cube-outline" class="text-6xl text-slate-600 mb-4"></ion-icon>
            <h3 class="text-xl font-semibold text-slate-300 mb-2">No tables found</h3>
            <p class="text-slate-500 mb-6">Get started by creating your first table</p>
            <a href="<?= route('dashboard.table.create') ?>" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-theme-primary hover:bg-theme-primary/80 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="add-outline"></ion-icon>
                Create Table
            </a>
        </div>
    <?php else: ?>
        <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-900/50 border-b border-slate-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Table Name</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php foreach ($tables as $table): ?>
                        <tr class="hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4">
                                <a href="<?= route('dashboard.table', ['table' => $table]) ?>" 
                                   class="text-theme-primary hover:text-theme-primary/80 font-medium">
                                    <?= e($table) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="<?= route('dashboard.table', ['table' => $table]) ?>" 
                                       class="text-blue-400 hover:text-blue-300 transition-colors" 
                                       title="View Details">
                                        <ion-icon name="eye-outline" class="text-xl"></ion-icon>
                                    </a>
                                    <form action="<?= route('dashboard.table.drop', ['table' => $table]) ?>" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this table? This action cannot be undone.');">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <?= csrf_field() ?>
                                        <button type="submit" 
                                                class="text-red-400 hover:text-red-300 transition-colors" 
                                                title="Delete Table">
                                            <ion-icon name="trash-outline" class="text-xl"></ion-icon>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

