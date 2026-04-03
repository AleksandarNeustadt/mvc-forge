{{-- Create Continent --}}

<div class="p-8 max-w-2xl">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Create Form --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        @php
        global $router;
        $lang = $router->lang ?? 'sr';
        
        $form = new FormBuilder("/{$lang}/dashboard/continents", 'POST');
        $form->class('space-y-6');
        
        // Display form errors if any
        if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Code
        $form->text('code', 'Continent Code')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., eu, as, na')
            ->attribute('maxlength', '10')
            ->help('2-letter continent code (e.g., eu, as, na)');

        // Name
        $form->text('name', 'Continent Name')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., Europe, Asia, North America');

        // Native Name
        $form->text('native_name', 'Native Name')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'Native name (optional)');

        // Sort Order
        $form->number('sort_order', 'Sort Order')
            ->default(0)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->attribute('placeholder', '0')
            ->help('Lower numbers appear first');

        // Is Active
        $form->checkbox('is_active', 'Active')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary')
            ->checked(true);

        // Submit button
        $form->submit('Create Continent')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        echo $form->render();
        @endphp
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="/{{ $lang }}/dashboard/continents" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Continents
        </a>
    </div>
</div>
