<section class="relative w-full max-w-7xl mx-auto px-4 py-8">
    
    <!-- Page Header -->
    <div class="mb-8 flex justify-between items-start">
        <div>
        </div>
        <div class="flex gap-3">
            <a href="<?= route('dashboard.column.create', ['table' => $table['name']]) ?>" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/80 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="add-outline"></ion-icon>
                Add Column
            </a>
            <a href="<?= route('dashboard.database') ?>" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-medium rounded-lg transition-colors">
                <ion-icon name="arrow-back-outline"></ion-icon>
                Back to Database Management
            </a>
        </div>
    </div>

    <!-- Table Info -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-slate-400 text-sm">Table Name</span>
                <p class="text-white font-medium"><?= e($table['name']) ?></p>
            </div>
            <div>
                <span class="text-slate-400 text-sm">Rows</span>
                <p class="text-white font-medium"><?= e($table['row_count'] ?? 0) ?></p>
            </div>
            <div>
                <span class="text-slate-400 text-sm">Columns</span>
                <p class="text-white font-medium"><?= e(count($table['columns'] ?? [])) ?></p>
            </div>
        </div>
    </div>

    <!-- Columns List -->
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-white mb-4">Columns</h2>
        
        <?php if (empty($table['columns'])): ?>
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-12 text-center">
                <ion-icon name="list-outline" class="text-6xl text-slate-600 mb-4"></ion-icon>
                <h3 class="text-xl font-semibold text-slate-300 mb-2">No columns found</h3>
                <p class="text-slate-500 mb-6">Add your first column to this table</p>
                <a href="<?= route('dashboard.column.create', ['table' => $table['name']]) ?>" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-theme-primary hover:bg-theme-primary/80 text-white font-medium rounded-lg transition-colors">
                    <ion-icon name="add-outline"></ion-icon>
                    Add Column
                </a>
            </div>
        <?php else: ?>
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Column Name</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Type</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Nullable</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Default</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Key</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($table['columns'] as $column): ?>
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4 text-white font-medium">
                                    <?= e($column['name']) ?>
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    <?= e($column['type']) ?>
                                    <?php if (!empty($column['length'])): ?>
                                        <span class="text-slate-500">(<?= e($column['length']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    <?php 
                                    $nullable = $column['nullable'] ?? 'NO';
                                    if (is_string($nullable)) {
                                        $nullable = strtoupper($nullable) === 'YES' ? 'Yes' : 'No';
                                    } else {
                                        $nullable = $nullable ? 'Yes' : 'No';
                                    }
                                    echo e($nullable);
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    <?= e($column['default_value'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $keyType = $column['key_type'] ?? '';
                                    if ($keyType === 'PRI') {
                                        echo '<span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 rounded text-xs font-medium">PRIMARY</span>';
                                    } elseif (!empty($keyType)) {
                                        echo '<span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded text-xs font-medium">' . e($keyType) . '</span>';
                                    } else {
                                        echo '<span class="text-slate-500">-</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4">
                                    <form action="<?= route('dashboard.column.drop', ['table' => $table['name'], 'column' => $column['name']]) ?>" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this column? This action cannot be undone.');">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <?= csrf_field() ?>
                                        <button type="submit" 
                                                class="text-red-400 hover:text-red-300 transition-colors" 
                                                title="Delete Column">
                                            <ion-icon name="trash-outline" class="text-xl"></ion-icon>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Indexes Section -->
    <?php if (!empty($table['indexes'])): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-semibold text-white mb-4">Indexes</h2>
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Index Name</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-slate-300">Definition</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($table['indexes'] as $index): ?>
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4 text-white font-medium">
                                    <?= e($index['name'] ?? $index['indexname'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    <?= e($index['definition'] ?? $index['sql'] ?? 'N/A') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</section>

