<section class="relative w-full max-w-3xl mx-auto px-4 py-8">
    
    <!-- Page Header -->
    <div class="mb-8">
    </div>

    <!-- Form Card -->
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-8">
        <?php
        echo Form::route('dashboard.column.store', 'POST', ['table' => $table])
            ->id('create-column-form')
            ->text('column_name', 'Column Name')
                ->required()
                ->placeholder('email')
                ->help('Only lowercase letters, numbers, and underscores.')
            ->select('column_type', 'Column Type', [
                'string' => 'String (VARCHAR)',
                'text' => 'Text',
                'integer' => 'Integer',
                'bigint' => 'Big Integer',
                'boolean' => 'Boolean',
                'float' => 'Float',
                'decimal' => 'Decimal',
                'date' => 'Date',
                'datetime' => 'DateTime (Timestamp)',
                'time' => 'Time',
                'json' => 'JSON'
            ])
                ->required()
            ->text('length', 'Length/Size')
                ->placeholder('255')
                ->help('For VARCHAR: max length. For DECIMAL: precision,scale (e.g., 10,2)')
            ->checkbox('nullable', 'Nullable')
                ->help('Allow NULL values')
            ->text('default', 'Default Value')
                ->placeholder('optional')
                ->help('Default value for the column')
            ->checkbox('unique', 'Unique')
                ->help('Add unique constraint to this column')
            ->submit('Add Column')
            ->close();
        ?>
    </div>

    <!-- Back Link -->
    <div class="mt-6">
        <a href="<?= route('dashboard.table', ['table' => $table]) ?>" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Table
        </a>
    </div>

</section>

