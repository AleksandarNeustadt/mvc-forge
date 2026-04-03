{{-- Create Table --}}

<section class="relative w-full max-w-3xl mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Form Card --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-8">
        @php
        echo Form::route('dashboard.table.store', 'POST')
            ->id('create-table-form')
            ->text('table_name', 'Table Name')
                ->required()
                ->placeholder('users')
                ->help('Only lowercase letters, numbers, and underscores. Will be automatically sanitized.')
            ->submit('Create Table')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors')
            ->close();
        @endphp
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="{{ route('dashboard.database') }}" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Database Management
        </a>
    </div>
</section>
