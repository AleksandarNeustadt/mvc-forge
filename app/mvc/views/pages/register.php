<!-- Register Page -->
<div class="w-full max-w-lg mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-theme-primary/20 mb-4">
            <ion-icon name="person-add-outline" class="text-3xl text-theme-primary"></ion-icon>
        </div>
        <h1 class="text-3xl font-bold text-white mb-2"><?= __('auth.register.title', 'Registracija') ?></h1>
        <p class="text-slate-400"><?= __('auth.register.subtitle', 'Kreirajte novi nalog i pridružite nam se.') ?></p>
    </div>

    <!-- Register Form -->
    <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-8">
        <?php
        echo Form::open("/{$lang}/register", 'POST')
            ->id('register-form')
            ->files() // Enable file uploads

            // Profile picture
            ->image('avatar', __('auth.avatar', 'Profilna slika'))
                ->accept('image/jpeg,image/png,image/webp')
                ->help(__('auth.avatar_help', 'JPG, PNG ili WebP. Maksimalno 2MB.'))

            // Divider
            ->divider(__('auth.personal_info', 'Lični podaci'))

            // Name fields in two columns
            ->raw('<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">')

            // First name
            ->text('first_name', __('auth.first_name', 'Ime'))
                ->required()
                ->placeholder('Petar')
                ->icon('person-outline')
                ->autocomplete('given-name')
                ->wrapperClass('space-y-2')

            // Last name
            ->text('last_name', __('auth.last_name', 'Prezime'))
                ->required()
                ->placeholder('Petrović')
                ->icon('person-outline')
                ->autocomplete('family-name')
                ->wrapperClass('space-y-2')

            ->raw('</div>')

            // Username
            ->text('username', __('auth.username', 'Korisničko ime'))
                ->required()
                ->minLength(3)
                ->maxLength(30)
                ->placeholder('petar_petrovic')
                ->icon('at-outline')
                ->autocomplete('username')
                ->help(__('auth.username_help', '3-30 karaktera, samo slova, brojevi i _'))

            // Email
            ->email('email', __('auth.email', 'Email adresa'))
                ->required()
                ->placeholder('vas@email.com')
                ->icon('mail-outline')
                ->autocomplete('email')

            // Divider
            ->divider(__('auth.security', 'Bezbednost'))

            // Password
            ->password('password', __('auth.password', 'Lozinka'))
                ->required()
                ->minLength(8)
                ->placeholder('••••••••')
                ->icon('lock-closed-outline')
                ->autocomplete('new-password')
                ->help(__('auth.password_help', 'Minimum 8 karaktera'))

            // Password confirmation
            ->password('password_confirmation', __('auth.password_confirm', 'Potvrdi lozinku'))
                ->required()
                ->minLength(8)
                ->placeholder('••••••••')
                ->icon('lock-closed-outline')
                ->autocomplete('new-password')

            // Divider
            ->divider()

            // Terms checkbox
            ->checkbox('terms', '')
                ->required()
            ->raw('<label for="terms" class="ml-3 text-sm text-slate-300">
                ' . __('auth.terms_agree', 'Slažem se sa') . '
                <a href="/' . $lang . '/terms" class="text-theme-primary hover:underline">' . __('auth.terms', 'uslovima korišćenja') . '</a>
                ' . __('auth.and', 'i') . '
                <a href="/' . $lang . '/privacy" class="text-theme-primary hover:underline">' . __('auth.privacy', 'politikom privatnosti') . '</a>
            </label>')

            // Newsletter checkbox (optional)
            ->checkbox('newsletter', __('auth.newsletter', 'Želim da primam novosti i obaveštenja'))

            // Honeypot field (hidden from users, but bots will fill it)
            ->raw('<div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">')
            ->text('website_url', 'Website URL')
                ->attr('tabindex', '-1')
                ->attr('autocomplete', 'off')
            ->raw('</div>')

            // Submit button
            ->submit(__('auth.register.button', 'Registruj se'))

            ->close();
        ?>
    </div>

    <!-- Login link -->
    <div class="mt-6 text-center">
        <p class="text-slate-400">
            <?= __('auth.have_account', 'Već imate nalog?') ?>
            <a href="/<?= $lang ?>/login" class="text-theme-primary hover:underline font-medium">
                <?= __('auth.login.link', 'Prijavite se') ?>
            </a>
        </p>
    </div>
</div>

