<?php
/**
 * Homepage View
 * 
 * Displays homepage with configurable components:
 * - Blog slider (optional)
 * - Login form (optional)
 * - Contact form (optional)
 */

// Get homepage options from display_options
$homepageOptions = [];
if (isset($page['display_options'])) {
    if (is_string($page['display_options'])) {
        $homepageOptions = json_decode($page['display_options'], true) ?? [];
    } elseif (is_array($page['display_options'])) {
        $homepageOptions = $page['display_options'];
    }
}

$enableBlogSlider = $homepageOptions['enable_blog_slider'] ?? false;
$blogSliderPosts = $homepageOptions['blog_slider_posts'] ?? [];
$enableLoginForm = $homepageOptions['enable_login_form'] ?? false;
$enableContactForm = $homepageOptions['enable_contact_form'] ?? false;

// Get language
global $router;
$lang = $router->lang ?? 'sr';

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>

<div class="w-full py-8">
    <?php if ($enableBlogSlider && !empty($blogSliderPosts)): ?>
        <!-- Blog Slider Section -->
        <section class="mb-12">
            <div class="relative w-full overflow-hidden rounded-lg border border-slate-700" style="aspect-ratio: 16/9;">
                <div id="blog-slider" class="relative w-full h-full">
                    <?php
                    $posts = [];
                    foreach ($blogSliderPosts as $postId) {
                        $post = BlogPost::find((int)$postId);
                        if ($post && $post->status === 'published') {
                            $posts[] = $post;
                        }
                    }
                    
                    if (!empty($posts)):
                        foreach ($posts as $index => $post):
                            $postArray = $post->toArray();
                            $categories = $post->categories();
                            $postArray['categories'] = is_array($categories) ? $categories : [];
                            $featuredImage = $postArray['featured_image'] ?? null;
                            
                            // Generate proper URL with category: /lang/category-slug/post-slug
                            $postUrl = null;
                            if (!empty($categories) && is_array($categories)) {
                                $primaryCategory = $categories[0];
                                if (isset($primaryCategory['slug'])) {
                                    $categorySlug = $primaryCategory['slug'];
                                    $postSlug = $post->slug;
                                    $postUrl = "/{$lang}/{$categorySlug}/{$postSlug}";
                                }
                            }
                            // Fallback: use direct slug if no category
                            if (!$postUrl) {
                                $postUrl = "/{$lang}/{$post->slug}";
                            }
                    ?>
                        <div class="blog-slide absolute inset-0 w-full h-full transition-opacity duration-500 ease-in-out <?= $index === 0 ? '' : 'pointer-events-none' ?>" style="<?= $index === 0 ? 'opacity: 1; z-index: 1;' : 'opacity: 0; z-index: 0;' ?>">
                            <!-- Background Image -->
                            <?php if ($featuredImage): ?>
                                <img src="<?= htmlspecialchars($featuredImage) ?>" 
                                     alt="<?= htmlspecialchars($postArray['title'] ?? '') ?>" 
                                     class="absolute inset-0 w-full h-full object-cover">
                            <?php else: ?>
                                <div class="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-900"></div>
                            <?php endif; ?>
                            
                            <!-- Dark Overlay for Text Readability -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/50 to-black/30"></div>
                            
                            <!-- Content Overlay -->
                            <div class="absolute inset-0 flex flex-col justify-end p-4 sm:p-6 md:p-8 lg:p-12">
                                <div class="max-w-4xl">
                                    <!-- Categories -->
                                    <?php if (!empty($postArray['categories'])): ?>
                                        <div class="flex flex-wrap gap-1.5 sm:gap-2 mb-2 sm:mb-3 md:mb-4">
                                            <?php foreach (array_slice($postArray['categories'], 0, 2) as $category): ?>
                                                <span class="px-2 py-0.5 sm:px-3 sm:py-1 bg-theme-primary/90 text-white rounded-full text-xs sm:text-sm font-medium backdrop-blur-sm">
                                                    <?= htmlspecialchars($category['name'] ?? $category['title'] ?? '') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Title -->
                                    <h3 class="text-lg sm:text-xl md:text-3xl lg:text-4xl xl:text-5xl font-bold text-white mb-2 sm:mb-3 md:mb-4 leading-tight drop-shadow-lg">
                                        <a href="<?= htmlspecialchars($postUrl) ?>" 
                                           class="hover:text-theme-primary transition-colors block">
                                            <?= htmlspecialchars($postArray['title'] ?? 'Untitled') ?>
                                        </a>
                                    </h3>
                                    
                                    <!-- Excerpt - Hidden on mobile, shown on tablet+ -->
                                    <?php if (!empty($postArray['excerpt'])): ?>
                                        <p class="hidden sm:block text-sm md:text-lg lg:text-xl text-white/90 mb-3 sm:mb-4 md:mb-6 line-clamp-2 drop-shadow-md max-w-3xl">
                                            <?= htmlspecialchars($postArray['excerpt']) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Meta Information - Smaller on mobile -->
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 md:gap-4 text-xs sm:text-sm md:text-base text-white/80 mb-3 sm:mb-4 md:mb-6">
                                        <?php if (!empty($postArray['published_at'])): ?>
                                            <span class="flex items-center gap-1 sm:gap-2 backdrop-blur-sm bg-black/30 px-2 py-0.5 sm:px-3 sm:py-1 rounded-full">
                                                <ion-icon name="calendar-outline" class="text-sm sm:text-base md:text-lg"></ion-icon>
                                                <span class="text-[10px] sm:text-xs md:text-sm"><?= date('d.m.Y', $postArray['published_at']) ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($postArray['views'])): ?>
                                            <span class="hidden sm:flex items-center gap-1 sm:gap-2 backdrop-blur-sm bg-black/30 px-2 py-0.5 sm:px-3 sm:py-1 rounded-full">
                                                <ion-icon name="eye-outline" class="text-sm sm:text-base md:text-lg"></ion-icon>
                                                <?= number_format($postArray['views']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Read More Button - Smaller on mobile -->
                                    <a href="<?= htmlspecialchars($postUrl) ?>" 
                                       class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 sm:px-4 sm:py-2 md:px-6 md:py-3 bg-theme-primary hover:bg-theme-primary/90 text-white text-xs sm:text-sm md:text-base font-medium rounded-lg transition-colors shadow-lg backdrop-blur-sm">
                                        <span class="hidden sm:inline">Pročitaj više</span>
                                        <span class="sm:hidden">Više</span>
                                        <ion-icon name="arrow-forward-outline" class="text-sm sm:text-base md:text-lg"></ion-icon>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
                
                <?php if (count($posts) > 1): ?>
                    <!-- Slider Navigation -->
                    <div class="absolute bottom-2 sm:bottom-3 md:bottom-4 left-1/2 transform -translate-x-1/2 z-10 flex items-center gap-1.5 sm:gap-2 bg-black/30 backdrop-blur-sm px-2 py-1 sm:px-3 sm:py-1.5 md:px-4 md:py-2 rounded-full">
                        <?php foreach ($posts as $index => $post): ?>
                            <button id="blog-slider-dot-<?= $index ?>" 
                                    class="blog-slider-dot rounded-full transition-all duration-300 <?= $index === 0 ? 'bg-theme-primary w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8' : 'bg-white/50 hover:bg-white/70 w-2 h-2 sm:w-2.5 sm:h-2.5 md:w-3 md:h-3' ?>"
                                    data-slide="<?= $index ?>"
                                    aria-label="Go to slide <?= $index + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Navigation Arrows - Hidden on mobile, shown on tablet+ -->
                    <button id="blog-slider-prev" 
                            class="hidden md:flex absolute left-2 md:left-4 top-1/2 transform -translate-y-1/2 z-10 w-10 h-10 md:w-12 md:h-12 bg-black/30 hover:bg-black/50 backdrop-blur-sm rounded-full items-center justify-center text-white transition-all duration-300 group"
                            aria-label="Previous slide">
                        <ion-icon name="chevron-back-outline" class="text-xl md:text-2xl group-hover:scale-110 transition-transform"></ion-icon>
                    </button>
                    <button id="blog-slider-next" 
                            class="hidden md:flex absolute right-2 md:right-4 top-1/2 transform -translate-y-1/2 z-10 w-10 h-10 md:w-12 md:h-12 bg-black/30 hover:bg-black/50 backdrop-blur-sm rounded-full items-center justify-center text-white transition-all duration-300 group"
                            aria-label="Next slide">
                        <ion-icon name="chevron-forward-outline" class="text-xl md:text-2xl group-hover:scale-110 transition-transform"></ion-icon>
                    </button>
                    
                    <script nonce="<?= csp_nonce() ?>">
                    document.addEventListener('DOMContentLoaded', function() {
                        (function() {
                            let currentSlide = 0;
                            const totalSlides = <?= count($posts) ?>;
                            let slideInterval = null;
                            
                            const slides = document.querySelectorAll('.blog-slide');
                            const dots = document.querySelectorAll('.blog-slider-dot');
                            const prevButton = document.getElementById('blog-slider-prev');
                            const nextButton = document.getElementById('blog-slider-next');
                            const sliderContainer = document.getElementById('blog-slider')?.closest('section');
                            
                            if (slides.length === 0) return;
                        
                        function showBlogSlide(index) {
                            if (index < 0 || index >= totalSlides) return;
                            
                            // Show/hide slides with fade effect
                            slides.forEach((slide, i) => {
                                if (i === index) {
                                    slide.style.zIndex = '2';
                                    slide.classList.remove('pointer-events-none');
                                    slide.style.opacity = '1';
                                } else {
                                    slide.style.opacity = '0';
                                    slide.style.zIndex = '0';
                                    // After transition, remove pointer events for hidden slides
                                    setTimeout(() => {
                                        if (slide.style.opacity === '0') {
                                            slide.classList.add('pointer-events-none');
                                        }
                                    }, 500);
                                }
                            });
                            
                            // Update dots with animation
                            dots.forEach((dot, i) => {
                                if (i === index) {
                                    // Active dot - update to active state
                                    dot.classList.remove('bg-white/50', 'bg-white/70', 'w-2', 'w-2.5', 'w-3', 'sm:w-2.5', 'md:w-3', 'h-2', 'h-2.5', 'h-3', 'sm:h-2.5', 'md:h-3');
                                    dot.classList.add('bg-theme-primary', 'w-6', 'sm:w-7', 'md:w-8', 'h-6', 'sm:h-7', 'md:h-8');
                                } else {
                                    // Inactive dot - restore to inactive state
                                    dot.classList.remove('bg-theme-primary', 'w-6', 'w-7', 'w-8', 'sm:w-7', 'md:w-8', 'h-6', 'h-7', 'h-8', 'sm:h-7', 'md:h-8');
                                    dot.classList.add('bg-white/50', 'w-2', 'sm:w-2.5', 'md:w-3', 'h-2', 'sm:h-2.5', 'md:h-3');
                                }
                            });
                            
                            currentSlide = index;
                            
                            // Reset auto-advance timer
                            resetAutoAdvance();
                        }
                        
                        function nextBlogSlide() {
                            const next = (currentSlide + 1) % totalSlides;
                            showBlogSlide(next);
                        }
                        
                        function previousBlogSlide() {
                            const prev = (currentSlide - 1 + totalSlides) % totalSlides;
                            showBlogSlide(prev);
                        }
                        
                        function resetAutoAdvance() {
                            if (slideInterval) {
                                clearInterval(slideInterval);
                            }
                            if (totalSlides > 1) {
                                slideInterval = setInterval(() => {
                                    nextBlogSlide();
                                }, 5000); // Change slide every 5 seconds
                            }
                        }
                        
                        // Add event listeners to dots
                        dots.forEach((dot, index) => {
                            dot.addEventListener('click', function() {
                                showBlogSlide(index);
                            });
                        });
                        
                        // Add event listeners to navigation arrows
                        if (prevButton) {
                            prevButton.addEventListener('click', previousBlogSlide);
                        }
                        if (nextButton) {
                            nextButton.addEventListener('click', nextBlogSlide);
                        }
                        
                        // Initialize auto-advance
                        if (totalSlides > 1) {
                            resetAutoAdvance();
                        }
                        
                        // Pause on hover
                        if (sliderContainer) {
                            sliderContainer.addEventListener('mouseenter', () => {
                                if (slideInterval) {
                                    clearInterval(slideInterval);
                                }
                            });
                            sliderContainer.addEventListener('mouseleave', () => {
                                resetAutoAdvance();
                            });
                        }
                        
                        // Swipe gesture for mobile devices
                        const sliderElement = document.getElementById('blog-slider');
                        if (sliderElement && totalSlides > 1) {
                            let touchStartX = null;
                            let touchEndX = null;
                            
                            const minSwipeDistance = 50; // Minimum distance in pixels for swipe
                            
                            sliderElement.addEventListener('touchstart', function(e) {
                                touchStartX = e.changedTouches[0].screenX;
                            }, { passive: true });
                            
                            sliderElement.addEventListener('touchend', function(e) {
                                touchEndX = e.changedTouches[0].screenX;
                                handleSwipe();
                            }, { passive: true });
                            
                            function handleSwipe() {
                                if (!touchStartX || !touchEndX) return;
                                
                                const swipeDistance = touchStartX - touchEndX;
                                
                                if (Math.abs(swipeDistance) > minSwipeDistance) {
                                    if (swipeDistance > 0) {
                                        // Swipe left - next slide
                                        nextBlogSlide();
                                    } else {
                                        // Swipe right - previous slide
                                        previousBlogSlide();
                                    }
                                }
                                
                                // Reset
                                touchStartX = null;
                                touchEndX = null;
                            }
                        }
                        })();
                    });
                    </script>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Main Content Grid -->
    <div class="grid md:grid-cols-2 gap-8">
        <?php if ($enableLoginForm): ?>
            <!-- Login Form Section -->
            <section class="bg-slate-900/50 rounded-lg border border-slate-700 p-6">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-theme-primary/20 mb-3">
                        <ion-icon name="log-in-outline" class="text-2xl text-theme-primary"></ion-icon>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2">Prijavite se</h2>
                    <p class="text-sm text-slate-400">Već imate nalog?</p>
                </div>
                
                <?php if (!$isAuthenticated): ?>
                    <?php if (Form::hasErrors()): ?>
                        <div class="mb-4 bg-red-900/20 border border-red-800/50 rounded-lg p-3">
                            <ul class="text-sm text-red-300 space-y-1">
                                <?php foreach (Form::getErrors() as $field => $messages): ?>
                                    <?php if (is_array($messages)): ?>
                                        <?php foreach ($messages as $message): ?>
                                            <li><?= htmlspecialchars($message) ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li><?= htmlspecialchars($messages) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/<?= $lang ?>/login" method="POST" class="space-y-4">
                        <?= CSRF::field() ?>
                        
                        <div>
                            <label for="login_email" class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                            <input type="email" 
                                   id="login_email" 
                                   name="email" 
                                   required
                                   autocomplete="email"
                                   class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary"
                                   placeholder="vas@email.com">
                        </div>
                        
                        <div>
                            <label for="login_password" class="block text-sm font-medium text-slate-300 mb-2">Lozinka</label>
                            <input type="password" 
                                   id="login_password" 
                                   name="password" 
                                   required
                                   autocomplete="current-password"
                                   class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary"
                                   placeholder="••••••••">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-slate-400">
                                <input type="checkbox" name="remember" class="w-4 h-4 text-theme-primary bg-slate-800 border-slate-700 rounded">
                                <span>Zapamti me</span>
                            </label>
                            <a href="/<?= $lang ?>/forgot-password" class="text-sm text-theme-primary hover:text-theme-primary/80">
                                Zaboravili ste lozinku?
                            </a>
                        </div>
                        
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                            Prijavite se
                        </button>
                        
                        <p class="text-center text-sm text-slate-400">
                            Nemate nalog? 
                            <a href="/<?= $lang ?>/register" class="text-theme-primary hover:text-theme-primary/80">
                                Registrujte se
                            </a>
                        </p>
                    </form>
                <?php else: ?>
                    <div class="text-center py-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-900/20 mb-4">
                            <ion-icon name="checkmark-circle-outline" class="text-3xl text-green-400"></ion-icon>
                        </div>
                        <p class="text-green-300 mb-4">Već ste prijavljeni!</p>
                        <a href="/<?= $lang ?>/profile" 
                           class="inline-flex items-center gap-2 px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                            <ion-icon name="person-outline"></ion-icon>
                            Vidi profil
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        
        <?php if ($enableContactForm): ?>
            <!-- Contact Form Section -->
            <section class="bg-slate-900/50 rounded-lg border border-slate-700 p-6">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-theme-primary/20 mb-3">
                        <ion-icon name="mail-outline" class="text-2xl text-theme-primary"></ion-icon>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2">Kontaktirajte nas</h2>
                    <p class="text-sm text-slate-400">Pošaljite nam poruku</p>
                </div>
                
                <?php if (Form::hasSuccess()): ?>
                    <div class="mb-4 bg-green-900/20 border border-green-800/50 rounded-lg p-3">
                        <p class="text-sm text-green-300"><?= htmlspecialchars(Form::getSuccess()) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!$isAuthenticated): ?>
                    <div class="mb-4 bg-yellow-900/20 border border-yellow-800/50 rounded-lg p-3">
                        <p class="text-sm text-yellow-300">Za slanje poruke potrebno je da budete prijavljeni.</p>
                        <a href="/<?= $lang ?>/login" class="text-theme-primary hover:text-theme-primary/80 text-sm mt-2 inline-block">
                            Prijavite se →
                        </a>
                    </div>
                <?php else: ?>
                    <?php if (Form::hasErrors()): ?>
                        <div class="mb-4 bg-red-900/20 border border-red-800/50 rounded-lg p-3">
                            <ul class="text-sm text-red-300 space-y-1">
                                <?php foreach (Form::getErrors() as $field => $messages): ?>
                                    <?php if (is_array($messages)): ?>
                                        <?php foreach ($messages as $message): ?>
                                            <li><?= htmlspecialchars($message) ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li><?= htmlspecialchars($messages) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Set form start time for anti-spam timing validation
                    Security::setFormStartTime('contact');
                    ?>
                    <form action="<?= $router->getUri() ?>" method="POST" class="space-y-4">
                        <?= CSRF::field() ?>
                        
                        <!-- Honeypot field (hidden from users, but bots will fill it) -->
                        <div style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">
                            <label for="website_url">Website URL</label>
                            <input type="text" 
                                   id="website_url" 
                                   name="website_url" 
                                   tabindex="-1"
                                   autocomplete="off"
                                   style="position: absolute; left: -9999px;">
                        </div>
                        
                        <div>
                            <label for="contact_subject" class="block text-sm font-medium text-slate-300 mb-2">Tema</label>
                            <input type="text" 
                                   id="contact_subject" 
                                   name="subject" 
                                   required
                                   minlength="3"
                                   maxlength="255"
                                   autocomplete="off"
                                   value="<?= htmlspecialchars(Form::old('subject', '')) ?>"
                                   class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary"
                                   placeholder="Tema poruke">
                        </div>
                        
                        <div>
                            <label for="contact_message" class="block text-sm font-medium text-slate-300 mb-2">Poruka</label>
                            <textarea id="contact_message" 
                                      name="message" 
                                      required
                                      minlength="10"
                                      maxlength="5000"
                                      rows="5"
                                      autocomplete="off"
                                      class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-theme-primary focus:ring-1 focus:ring-theme-primary"
                                      placeholder="Vaša poruka..."><?= htmlspecialchars(Form::old('message', '')) ?></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-theme-primary hover:bg-theme-primary/90 text-white font-medium rounded-lg transition-colors">
                            Pošalji poruku
                        </button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
    
    <?php if (!$enableLoginForm && !$enableContactForm): ?>
        <!-- Default content if no components enabled -->
        <div class="text-center py-12">
            <h1 class="text-3xl font-bold text-white mb-4"><?= htmlspecialchars($page['title'] ?? 'Homepage') ?></h1>
            <?php if (!empty($page['content'])): ?>
                <div class="prose prose-invert max-w-none text-slate-300">
                    <?= Security::sanitizeHtml($page['content']) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
