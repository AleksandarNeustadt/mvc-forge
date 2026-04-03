{{-- Edit Language Page --}}

@php
global $router;
$lang = $router->lang ?? 'sr';

// Ensure language is an array
if (isset($language) && is_object($language)) {
    $language = (array) $language;
}
if (!isset($language) || !is_array($language)) {
    $language = [];
}

$continents = $continents ?? [];
$regions = $regions ?? [];
$languageId = $language['id'] ?? 0;
@endphp

<div class="p-8 max-w-2xl">
    {{-- Page Header --}}
    <div class="mb-8">
    </div>

    {{-- Edit Form --}}
    <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
        @php
        $form = new FormBuilder("/{$lang}/dashboard/languages/{$languageId}", 'POST');
        $form->class('space-y-6');
        
        // Add PUT method override
        $form->hidden('_method', 'PUT');
        
        // Display form errors if any
        if (class_exists('Form') && method_exists('Form', 'hasErrors') && Form::hasErrors()) {
            $form->errors(Form::getErrors());
        }

        // Code
        $form->text('code', 'Language Code')
            ->required()
            ->value($language['code'] ?? '')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., sr, en, de')
            ->attribute('maxlength', '10')
            ->help('ISO 639-1 language code (2-10 characters)');

        // Name
        $form->text('name', 'Language Name')
            ->required()
            ->value($language['name'] ?? '')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., Serbian, English, German');

        // Native Name
        $form->text('native_name', 'Native Name')
            ->required()
            ->value($language['native_name'] ?? '')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., Српски, English, Deutsch');

        // Flag
        $form->text('flag', 'Flag Emoji')
            ->value($language['flag'] ?? '')
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('placeholder', 'e.g., 🇷🇸, 🇬🇧, 🇩🇪')
            ->attribute('maxlength', '10')
            ->help('Flag emoji or code (optional)');

        // Continent
        if (!empty($continents)) {
            $form->select('continent_id', 'Continent', $continents)
                ->value($language['continent_id'] ?? '')
                ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
                ->attribute('id', 'continent_id_select');
        }

        // Region
        if (!empty($regions)) {
            $form->select('region_id', 'Region', $regions)
                ->value($language['region_id'] ?? '')
                ->fieldClass('w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
                ->attribute('id', 'region_id_select')
                ->help('Select a continent first to filter regions');
        }

        // Sort Order
        $form->number('sort_order', 'Sort Order')
            ->value($language['sort_order'] ?? 0)
            ->attribute('class', 'w-full px-4 py-2 bg-slate-900/50 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary')
            ->attribute('min', '0')
            ->attribute('placeholder', '0')
            ->help('Lower numbers appear first');

        // Is Active
        $form->checkbox('is_active', 'Active')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary')
            ->checked($language['is_active'] ?? false);

        // Is Default
        $form->checkbox('is_default', 'Set as Default')
            ->attribute('class', 'w-4 h-4 text-theme-primary bg-slate-900 border-slate-700 rounded focus:ring-theme-primary')
            ->checked($language['is_default'] ?? false)
            ->help('Only one language can be default');

        // Submit button
        $form->submit('Update Language')
            ->attribute('class', 'w-full px-6 py-3 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors');

        echo $form->render();
        @endphp
    </div>

    {{-- Back Link --}}
    <div class="mt-6">
        <a href="/{{ $lang }}/dashboard/languages" 
           class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Languages
        </a>
    </div>
</div>
