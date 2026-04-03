<section class="relative flex flex-col items-center justify-center w-full px-4 py-10">

    <!-- Subtle ambient glows that interact with starfield - CONTAINED -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 sm:w-96 sm:h-96 bg-theme-primary/5 rounded-full blur-3xl animate-pulse-slow" style="transform: translateX(-50%) translateY(-50%);"></div>
        <div class="absolute bottom-1/4 right-1/4 w-64 h-64 sm:w-96 sm:h-96 bg-theme-primary/5 rounded-full blur-3xl animate-pulse-slow animation-delay-2000" style="transform: translateX(50%) translateY(50%);"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 text-center space-y-4 sm:space-y-6 max-w-4xl mx-auto w-full">

        <!-- Lock Icon with Animation -->
        <div class="relative inline-block">
            <div class="absolute inset-0 bg-theme-primary/20 blur-3xl animate-pulse-slow"></div>
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-theme-primary via-theme-primary to-theme-primary rounded-full opacity-75 group-hover:opacity-100 blur-sm transition duration-1000 group-hover:duration-200 animate-pulse-slow"></div>
                <div class="relative bg-slate-900/90 backdrop-blur-sm p-6 sm:p-8 rounded-full border border-theme-primary/50 shadow-2xl">
                    <svg class="w-16 h-16 sm:w-20 sm:h-20 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Heading -->
        <div class="space-y-2">
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-black tracking-tighter text-white">
                403
            </h1>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight text-theme-primary">
                <?= __('page.403.title') ?? 'Access Forbidden' ?>
            </h2>
        </div>

        <!-- Description -->
        <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed px-4">
            <?= __('page.403.description') ?? 'You do not have permission to access this resource.' ?>
        </p>

        <!-- Back Button -->
        <div class="flex justify-center items-center pt-4 sm:pt-6 px-4 gap-4">
            <a href="/<?= $lang ?? 'sr' ?>" class="group relative overflow-hidden bg-theme-primary/20 backdrop-blur-sm px-6 sm:px-8 py-3 sm:py-4 rounded-xl border border-theme-primary/50 hover:border-theme-primary transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-theme-primary/30">
                <span class="absolute inset-0 bg-gradient-to-r from-theme-primary/0 via-theme-primary/20 to-theme-primary/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></span>
                <span class="relative flex items-center gap-2 sm:gap-3 text-sm sm:text-base font-semibold text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <?= __('page.403.backButton') ?? 'Go Home' ?>
                </span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/<?= $lang ?? 'sr' ?>/profile" class="group relative overflow-hidden bg-slate-800/50 backdrop-blur-sm px-6 sm:px-8 py-3 sm:py-4 rounded-xl border border-slate-700/50 hover:border-slate-600 transition-all duration-300 hover:scale-105">
                    <span class="relative flex items-center gap-2 sm:gap-3 text-sm sm:text-base font-semibold text-slate-300 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <?= __('page.403.profileButton') ?? 'My Profile' ?>
                    </span>
                </a>
            <?php endif; ?>
        </div>
    </div>

</section>

