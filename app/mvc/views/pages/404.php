<section class="relative flex flex-col items-center justify-center w-full px-4 py-10">

    <!-- Subtle ambient glows that interact with starfield - CONTAINED -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 sm:w-96 sm:h-96 bg-theme-primary/5 rounded-full blur-3xl animate-pulse-slow" style="transform: translateX(-50%) translateY(-50%);"></div>
        <div class="absolute bottom-1/4 right-1/4 w-64 h-64 sm:w-96 sm:h-96 bg-theme-primary/5 rounded-full blur-3xl animate-pulse-slow animation-delay-2000" style="transform: translateX(50%) translateY(50%);"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 text-center space-y-4 sm:space-y-6 max-w-4xl mx-auto w-full">

        <!-- Planet Icon with Animation -->
        <div class="relative inline-block">
            <div class="absolute inset-0 bg-theme-primary/20 blur-3xl animate-pulse-slow"></div>
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-theme-primary via-theme-primary to-theme-primary rounded-full opacity-75 group-hover:opacity-100 blur-sm transition duration-1000 group-hover:duration-200 animate-pulse-slow"></div>
                <div class="relative bg-slate-900/90 backdrop-blur-sm p-6 sm:p-8 rounded-full border border-theme-primary/50 shadow-2xl">
                    <svg class="w-16 h-16 sm:w-20 sm:h-20 text-theme-primary" fill="currentColor" viewBox="0 0 512 512">
                        <path d="M413.48 284.46c58.87 47.24 91.61 89 80.31 108.55-17.85 30.85-138.78-5.48-270.1-81.15S.37 149.84 18.21 119c11.16-19.28 62.58-12.32 131.64 14.09"/>
                        <circle cx="256" cy="256" r="160"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Heading -->
        <div class="space-y-2">
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-black tracking-tighter text-white">
                404
            </h1>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight text-theme-primary">
                <?= __('page.404.title') ?>
            </h2>
        </div>

        <!-- Description -->
        <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed px-4">
            <?= __('page.404.description') ?>
        </p>

        <!-- Back Button -->
        <div class="flex justify-center items-center pt-4 sm:pt-6 px-4">
            <a href="/<?= $lang ?? 'sr' ?>" class="group relative overflow-hidden bg-theme-primary/20 backdrop-blur-sm px-6 sm:px-8 py-3 sm:py-4 rounded-xl border border-theme-primary/50 hover:border-theme-primary transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-theme-primary/30">
                <span class="absolute inset-0 bg-gradient-to-r from-theme-primary/0 via-theme-primary/20 to-theme-primary/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></span>
                <span class="relative flex items-center gap-2 sm:gap-3 text-sm sm:text-base font-semibold text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <?= __('page.404.backButton') ?>
                </span>
            </a>
        </div>
    </div>

</section>

