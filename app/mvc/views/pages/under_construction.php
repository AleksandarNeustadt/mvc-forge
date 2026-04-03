<section class="relative flex flex-col items-center justify-center w-full px-4 py-10">

    <!-- Subtle ambient glows that interact with starfield - CONTAINED -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 sm:w-96 sm:h-96 bg-theme-primary/5 rounded-full blur-3xl animate-pulse-slow" style="transform: translateX(-50%) translateY(-50%);"></div>
        <div class="absolute bottom-1/4 right-1/4 w-64 h-64 sm:w-96 sm:h-96 bg-theme-primary/5 rounded-full blur-3xl animate-pulse-slow animation-delay-2000" style="transform: translateX(50%) translateY(50%);"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 text-center space-y-4 sm:space-y-6 max-w-4xl mx-auto w-full">

        <!-- Rocket Icon with Animation -->
        <div class="relative inline-block">
            <div class="absolute inset-0 bg-theme-primary/20 blur-3xl animate-pulse-slow"></div>
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-theme-primary via-theme-primary to-theme-primary rounded-full opacity-75 group-hover:opacity-100 blur-sm transition duration-1000 group-hover:duration-200 animate-pulse-slow"></div>
                <div class="relative bg-slate-900/90 backdrop-blur-sm p-6 sm:p-8 rounded-full border border-theme-primary/50 shadow-2xl">
                    <svg class="w-16 h-16 sm:w-20 sm:h-20 text-theme-primary" fill="currentColor" viewBox="0 0 512 512">
                        <path d="M461.81 53.81a4.4 4.4 0 00-3.3-3.39c-54.38-13.3-180 34.09-248.13 102.17a294.9 294.9 0 00-33.09 39.08c-21-1.9-42-.3-59.88 7.5-50.49 22.2-65.18 80.18-69.28 105.07a9 9 0 009.8 10.4l81.07-8.9a180.29 180.29 0 001.1 18.3 18.15 18.15 0 005.3 11.09l31.39 31.39a18.15 18.15 0 0011.1 5.3 179.91 179.91 0 0018.19 1.1l-8.89 81a9 9 0 0010.39 9.79c24.9-4 83-18.69 105.07-69.17 7.8-17.9 9.4-38.79 7.6-59.69a293.91 293.91 0 0039.19-33.09c68.38-68 115.47-190.86 102.37-247.95zM298.66 213.67a42.7 42.7 0 1160.38 0 42.65 42.65 0 01-60.38 0z"/>
                        <path d="M109.64 352a45.06 45.06 0 00-26.35 12.84C65.67 382.52 64 448 64 448s65.52-1.67 83.15-19.31A44.73 44.73 0 00160 402.32a16 16 0 00-15.17-16.42 16.17 16.17 0 00-11.19 4.32 12.8 12.8 0 01-9.37 3.48C116.28 393.7 112 386.62 112 378.63c0-3.48 1.18-6.79 3.48-9.37a16 16 0 00-5.84-26.26z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Heading - Clean and Simple -->
        <div class="space-y-2">
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-black tracking-tighter text-white">
                <?= __('page.underConstruction.titlePart1') ?>
            </h1>
            <h1 class="text-6xl sm:text-7xl md:text-8xl font-black tracking-tighter text-theme-primary">
                <?= __('page.underConstruction.titlePart2') ?>
            </h1>
        </div>

        <!-- Description -->
        <p class="text-base sm:text-lg md:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed px-4">
            <?= __('page.underConstruction.description') ?>
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-4 sm:gap-6 justify-center items-center pt-4 sm:pt-6 px-4">
            <!-- Login Button -->
            <a href="/<?= $lang ?? 'sr' ?>/login" class="group relative overflow-hidden bg-slate-800/60 backdrop-blur-sm px-6 sm:px-8 py-3 sm:py-4 rounded-xl border border-slate-700/50 hover:border-theme-primary/70 transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-theme-primary/20">
                <span class="absolute inset-0 bg-gradient-to-r from-theme-primary/0 via-theme-primary/10 to-theme-primary/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></span>
                <span class="relative flex items-center gap-2 sm:gap-3 text-sm sm:text-base font-semibold text-slate-300 group-hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-theme-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <?= __('page.underConstruction.crewAccess') ?>
                </span>
            </a>

            <!-- Register Button -->
            <a href="/<?= $lang ?? 'sr' ?>/register" class="group relative overflow-hidden bg-theme-primary/20 backdrop-blur-sm px-6 sm:px-8 py-3 sm:py-4 rounded-xl border border-theme-primary/50 hover:border-theme-primary transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-theme-primary/30">
                <span class="absolute inset-0 bg-gradient-to-r from-theme-primary/0 via-theme-primary/20 to-theme-primary/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></span>
                <span class="relative flex items-center gap-2 sm:gap-3 text-sm sm:text-base font-semibold text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                    <?= __('page.underConstruction.boardShip') ?>
                </span>
            </a>
        </div>
    </div>

</section>

