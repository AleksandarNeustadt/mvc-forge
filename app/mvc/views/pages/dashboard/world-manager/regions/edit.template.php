{{-- Edit Region --}}

<div class="p-8 max-w-2xl">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Edit Form --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        @php
        global $router;
        $lang = $router->lang ?? 'sr';
        
        $form = new FormBuilder("/{$lang}/dashboard/regions/{$region['id']}", 'PUT');
        $form->class('space-y-6');
        
        // Display form errors if any
        if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Continent
        if (isset($continents) && !empty($continents)) {
            $form->select('continent_id', 'Continent', $continents)
                ->required()
                ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary');
        }

        // Code
        $form->text('code', 'Region Code')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., western-europe, eastern-europe')
            ->attribute('maxlength', '20')
            ->help('Region code (optional)');

        // Name
        $form->text('name', 'Region Name')
            ->required()
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., Western Europe, Eastern Europe');

        // Native Name
        $form->text('native_name', 'Native Name')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'Native name (optional)');

        // Description
        $form->textarea('description', 'Description')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('rows', '3')
            ->attribute('placeholder', 'Optional description');

        // Sort Order
        $form->number('sort_order', 'Sort Order')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->attribute('placeholder', '0')
            ->help('Lower numbers appear first');

        // Is Active
        $form->checkbox('is_active', 'Active')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary');

        // Submit button
        $form->submit('Update Region')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        echo $form->render();
        @endphp
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="/{{ $lang }}/dashboard/regions" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Regions
        </a>
    </div>
</div>
