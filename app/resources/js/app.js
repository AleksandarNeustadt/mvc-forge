import '../css/app.css';
import { Starfield } from './starfield.js';

// Global flag code mapping (same as PHP get_flag_code helper)
const flagCodeMap = {
    'sr': 'rs', 'hr': 'hr', 'bg': 'bg', 'ro': 'ro', 'sl': 'si', 'el': 'gr', 'mk': 'mk',
    'en': 'gb', 'de': 'de', 'fr': 'fr', 'es': 'es', 'it': 'it', 'pt': 'pt', 'nl': 'nl',
    'pl': 'pl', 'ru': 'ru', 'uk': 'ua', 'cs': 'cz', 'sk': 'sk', 'hu': 'hu',
    'sv': 'se', 'da': 'dk', 'no': 'no', 'fi': 'fi',
    'lt': 'lt', 'et': 'ee', 'lv': 'lv',
    'zh': 'cn', 'ja': 'jp', 'ko': 'kr', 'tr': 'tr'
};

// Global function to get flag code (same logic as PHP get_flag_code helper)
window.getFlagCode = function(langCode, flagFromDb = null) {
    // Always use the language code mapping for flag-icons library
    // Ignore emoji from database as flag-icons uses 2-letter country codes
    const code = (langCode || '').toLowerCase();
    return flagCodeMap[code] || code || 'xx';
};

// Global starfield instance
let starfield = null;

// Theme colors configuration
const themes = {
    cyan: {
        primary: '6, 182, 212',      // cyan-500
        primaryHex: '#06b6d4'
    },
    purple: {
        primary: '168, 85, 247',     // purple-500
        primaryHex: '#a855f7'
    },
    pink: {
        primary: '236, 72, 153',     // pink-500
        primaryHex: '#ec4899'
    },
    emerald: {
        primary: '16, 185, 129',     // emerald-500
        primaryHex: '#10b981'
    },
    orange: {
        primary: '249, 115, 22',      // orange-500
        primaryHex: '#f97316'
    },
    red: {
        primary: '239, 68, 68',       // red-500
        primaryHex: '#ef4444'
    }
};

// Initialize theme system
function initTheme() {
    // Load saved theme or default to cyan
    const savedTheme = localStorage.getItem('theme') || 'cyan';
    applyTheme(savedTheme);

    // Color picker elements
    const toggle = document.getElementById('colorPickerToggle');
    const container = document.getElementById('colorPickerContainer');
    const colors = document.getElementById('colorPickerColors');

    if (toggle && container) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = !container.classList.contains('translate-x-[calc(100%-48px)]');

            if (isVisible) {
                closeColorPicker();
            } else {
                openColorPicker();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                closeColorPicker();
            }
        });

        // Theme buttons
        const themeButtons = document.querySelectorAll('.theme-btn');
        themeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const theme = btn.getAttribute('data-theme');
                applyTheme(theme);
                localStorage.setItem('theme', theme);

                // Update active state
                themeButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Close after selection
                setTimeout(() => closeColorPicker(), 300);
            });
        });

        // Set initial active button
        const activeBtn = document.querySelector(`[data-theme="${savedTheme}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
}

function openColorPicker() {
    const container = document.getElementById('colorPickerContainer');
    const colors = document.getElementById('colorPickerColors');
    if (container && colors) {
        container.classList.remove('translate-x-[calc(100%-48px)]');
        container.classList.add('translate-x-0');
        colors.classList.remove('opacity-0');
        colors.classList.add('opacity-100');
    }
}

function closeColorPicker() {
    const container = document.getElementById('colorPickerContainer');
    const colors = document.getElementById('colorPickerColors');
    if (container && colors) {
        container.classList.add('translate-x-[calc(100%-48px)]');
        container.classList.remove('translate-x-0');
        colors.classList.add('opacity-0');
        colors.classList.remove('opacity-100');
    }
}

function applyTheme(themeName) {
    const theme = themes[themeName];
    if (!theme) return;

    // Apply CSS custom properties
    document.documentElement.style.setProperty('--theme-primary-rgb', theme.primary);
    document.documentElement.style.setProperty('--theme-primary-hex', theme.primaryHex);

    // Add theme class to body
    document.body.className = document.body.className.replace(/theme-\w+/g, '');
    document.body.classList.add(`theme-${themeName}`);

}

// Initialize starfield only on pages that include the canvas (e.g. some landings)
function initStarfield() {
    const canvas = document.getElementById('starfield-canvas');
    if (!canvas) {
        return;
    }
    starfield = new Starfield('starfield-canvas', {
        starCount: 300,
        maxSize: 1.2,
        minSize: 0.3,
        maxSpeed: 0.08,
        minSpeed: 0.01,
        twinkleSpeed: 0.005,
        baseOpacity: 0.7
    });
}

// Initialize language selector
function initLanguageSelector(toggleId = 'language-toggle', dropdownId = 'language-dropdown', arrowId = 'dropdown-arrow') {
    const toggle = document.getElementById(toggleId);
    const dropdown = document.getElementById(dropdownId);
    const arrow = document.getElementById(arrowId);

    if (toggle && dropdown) {
        // Check if this language selector is inside user menu
        const userMenuDropdown = document.getElementById('user-dropdown');
        const isInsideUserMenu = userMenuDropdown && userMenuDropdown.contains(toggle);
        
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            // Don't prevent default for button clicks - we want normal button behavior
            
            const isVisible = !dropdown.classList.contains('hidden');

            if (isVisible) {
                closeLanguageDropdown(toggle, dropdown, arrow);
            } else {
                // Close other language dropdowns first (but not this one)
                closeAllLanguageDropdowns(dropdownId);
                // Small delay to ensure other dropdowns are closed before opening this one
                setTimeout(() => {
                    openLanguageDropdown(toggle, dropdown, arrow);
                }, 0);
            }
        });

        // Click outside handler - use capture phase and check if dropdown is actually visible
        document.addEventListener('click', (e) => {
            // Small delay to allow dropdown to open first
            setTimeout(() => {
                // Don't close if clicking inside the dropdown or toggle
                if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
                    // Only close if dropdown is actually visible
                    const isVisible = !dropdown.classList.contains('hidden') || 
                                     window.getComputedStyle(dropdown).display !== 'none';
                    
                    if (!isVisible) return;
                    
                    // If inside user menu, check if clicking elsewhere in user menu
                    if (isInsideUserMenu && userMenuDropdown) {
                        // If clicking inside user menu but not on language elements, close language dropdown
                        if (userMenuDropdown.contains(e.target) && !e.target.closest('[id*="language"]')) {
                            closeLanguageDropdown(toggle, dropdown, arrow);
                        } 
                        // If clicking outside user menu entirely, language dropdown will be closed when user menu closes
                        else if (!userMenuDropdown.contains(e.target)) {
                            closeLanguageDropdown(toggle, dropdown, arrow);
                        }
                    } else {
                        // Standalone language selector - close on outside click
                        closeLanguageDropdown(toggle, dropdown, arrow);
                    }
                }
            }, 10); // Small delay to allow dropdown to open
        }, true); // Use capture phase

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !dropdown.classList.contains('hidden')) {
                closeLanguageDropdown(toggle, dropdown, arrow);
            }
        });
    }

    function openLanguageDropdown(toggle, dropdown, arrow) {
        if (!toggle || !dropdown) {
            console.warn('openLanguageDropdown: toggle or dropdown is missing', { toggle, dropdown });
            return;
        }
        
        console.log('openLanguageDropdown: Opening dropdown', { 
            dropdownId: dropdown.id, 
            toggleId: toggle.id,
            isHidden: dropdown.classList.contains('hidden'),
            isInDOM: document.body.contains(dropdown),
            computedDisplay: window.getComputedStyle(dropdown).display,
            computedVisibility: window.getComputedStyle(dropdown).visibility,
            computedOpacity: window.getComputedStyle(dropdown).opacity
        });
        
        // Check if dropdown is inside user menu
        const userMenuDropdown = document.getElementById('user-dropdown');
        const isInsideUserMenu = userMenuDropdown && (userMenuDropdown.contains(toggle) || toggle.closest('#user-dropdown'));
        
        console.log('openLanguageDropdown: Context', { 
            isInsideUserMenu, 
            userMenuExists: !!userMenuDropdown,
            userMenuVisible: userMenuDropdown ? !userMenuDropdown.classList.contains('hidden') : false,
            userMenuDisplay: userMenuDropdown ? window.getComputedStyle(userMenuDropdown).display : 'N/A'
        });
        
        // If inside user menu, use fixed positioning and calculate position dynamically
        if (isInsideUserMenu && toggle && userMenuDropdown) {
            // Check if dropdown is already in body (if we moved it before)
            const isInBody = dropdown.parentElement === document.body;
            
            // Always move dropdown to body when it's inside user menu
            // This ensures position: fixed works correctly regardless of parent positioning context
            if (!isInBody) {
                console.log('openLanguageDropdown: Moving dropdown to body for proper fixed positioning');
                // Store original parent container for later restoration
                const languageContainer = toggle.closest('.relative, .group\\/language') || toggle.parentElement;
                if (languageContainer) {
                    dropdown.dataset.originalParent = languageContainer.id || 'user-menu-language-container';
                    dropdown.dataset.originalParentElement = languageContainer.tagName + (languageContainer.className ? '.' + languageContainer.className.split(' ')[0] : '');
                }
                // Move dropdown to body
                document.body.appendChild(dropdown);
                console.log('openLanguageDropdown: Dropdown moved to body', {
                    wasIn: dropdown.dataset.originalParentElement || 'unknown',
                    nowIn: dropdown.parentElement.tagName
                });
            }
            
            // Get initial position immediately for better UX
            const initialToggleRect = toggle.getBoundingClientRect();
            const initialUserMenuRect = userMenuDropdown.getBoundingClientRect();
            
            // Remove hidden class and make visible first
            // Use setProperty with important flag to override Tailwind's !important hidden class
            dropdown.classList.remove('hidden');
            
            // Force remove any inline styles that might interfere
            dropdown.style.removeProperty('display');
            dropdown.style.removeProperty('visibility');
            dropdown.style.removeProperty('opacity');
            
            // Now set with important
            dropdown.style.setProperty('display', 'block', 'important');
            dropdown.style.setProperty('visibility', 'visible', 'important');
            dropdown.style.setProperty('opacity', '1', 'important');
            dropdown.style.setProperty('position', 'fixed', 'important');
            dropdown.style.setProperty('z-index', '9999', 'important'); // Very high z-index to ensure visibility
            dropdown.style.setProperty('width', '288px', 'important'); // w-72 = 18rem = 288px
            dropdown.style.setProperty('max-height', '80vh', 'important');
            dropdown.style.setProperty('overflow-y', 'auto', 'important');
            dropdown.style.setProperty('pointer-events', 'auto', 'important');
            
            console.log('openLanguageDropdown: Applied initial styles', {
                display: dropdown.style.display,
                visibility: dropdown.style.visibility,
                opacity: dropdown.style.opacity,
                position: dropdown.style.position,
                zIndex: dropdown.style.zIndex,
                hasHiddenClass: dropdown.classList.contains('hidden'),
                computedDisplay: window.getComputedStyle(dropdown).display,
                computedVisibility: window.getComputedStyle(dropdown).visibility,
                computedOpacity: window.getComputedStyle(dropdown).opacity
            });
            
            // Check if parent has transform (which would make fixed behave like absolute)
            const userMenuComputedStyle = window.getComputedStyle(userMenuDropdown);
            const hasTransform = userMenuComputedStyle.transform && userMenuComputedStyle.transform !== 'none';
            const hasFilter = userMenuComputedStyle.filter && userMenuComputedStyle.filter !== 'none';
            const hasPerspective = userMenuComputedStyle.perspective && userMenuComputedStyle.perspective !== 'none';
            const hasWillChange = userMenuComputedStyle.willChange && userMenuComputedStyle.willChange !== 'auto';
            
            console.log('openLanguageDropdown: Parent element CSS properties', {
                transform: userMenuComputedStyle.transform,
                filter: userMenuComputedStyle.filter,
                perspective: userMenuComputedStyle.perspective,
                willChange: userMenuComputedStyle.willChange,
                position: userMenuComputedStyle.position,
                hasTransform,
                hasFilter,
                hasPerspective,
                hasWillChange
            });
            
            if (hasTransform || hasFilter || hasPerspective || hasWillChange) {
                console.warn('openLanguageDropdown: User menu has CSS properties that may affect fixed positioning', {
                    hasTransform,
                    hasFilter,
                    hasPerspective,
                    hasWillChange
                });
            }
            
            // Set initial position immediately if we have valid dimensions
            if (initialToggleRect.width > 0 && initialUserMenuRect.width > 0) {
                const dropdownWidth = 288;
                const margin = 12;
                const viewportWidth = window.innerWidth;
                console.log('openLanguageDropdown: Setting initial position immediately', {
                    initialToggleRect: {
                        left: initialToggleRect.left,
                        top: initialToggleRect.top,
                        width: initialToggleRect.width,
                        height: initialToggleRect.height,
                        right: initialToggleRect.right
                    },
                    initialUserMenuRect: {
                        left: initialUserMenuRect.left,
                        top: initialUserMenuRect.top,
                        width: initialUserMenuRect.width,
                        height: initialUserMenuRect.height,
                        right: initialUserMenuRect.right
                    },
                    dropdownWidth,
                    viewportWidth
                });
                positionLanguageDropdown(dropdown, initialToggleRect, initialUserMenuRect, dropdownWidth, margin, viewportWidth);
            } else {
                // Fallback: if dimensions are not available, position relative to toggle button
                console.warn('openLanguageDropdown: Using fallback positioning due to invalid dimensions', {
                    toggleWidth: initialToggleRect.width,
                    toggleHeight: initialToggleRect.height,
                    userMenuWidth: initialUserMenuRect.width,
                    userMenuHeight: initialUserMenuRect.height
                });
                const toggleRect = toggle.getBoundingClientRect();
                const fallbackLeft = Math.max(12, toggleRect.left - 300);
                const fallbackTop = toggleRect.top;
                dropdown.style.setProperty('left', `${fallbackLeft}px`, 'important');
                dropdown.style.setProperty('top', `${fallbackTop}px`, 'important');
                console.log('openLanguageDropdown: Applied fallback positioning', {
                    left: fallbackLeft,
                    top: fallbackTop
                });
            }
            
            // Use triple requestAnimationFrame to ensure DOM is fully updated and refine position
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        // Get dimensions - user menu should be visible since we're clicking Language inside it
                        const toggleRect = toggle.getBoundingClientRect();
                        const userMenuRect = userMenuDropdown.getBoundingClientRect();
                        const dropdownWidth = 288; // w-72 = 18rem = 288px
                        const margin = 12; // 0.75rem = 12px - space between dropdowns
                        const viewportWidth = window.innerWidth;
                        
                        // Ensure we have valid dimensions
                        if (toggleRect.width === 0 || toggleRect.height === 0 || userMenuRect.width === 0 || userMenuRect.height === 0) {
                            // If dimensions are invalid, try again after a short delay
                            setTimeout(() => {
                                const retryToggleRect = toggle.getBoundingClientRect();
                                const retryUserMenuRect = userMenuDropdown.getBoundingClientRect();
                                if (retryToggleRect.width > 0 && retryUserMenuRect.width > 0) {
                                    positionLanguageDropdown(dropdown, retryToggleRect, retryUserMenuRect, dropdownWidth, margin, viewportWidth);
                                }
                            }, 50);
                            return;
                        }
                        
                        // Refine position with accurate measurements
                        positionLanguageDropdown(dropdown, toggleRect, userMenuRect, dropdownWidth, margin, viewportWidth);
                    });
                });
            });
        } else {
            // Standalone language selector - just remove hidden class
            dropdown.classList.remove('hidden');
            dropdown.style.setProperty('display', 'block', 'important');
            dropdown.style.setProperty('visibility', 'visible', 'important');
            dropdown.style.setProperty('opacity', '1', 'important');
        }
        
        // Update arrow and toggle state
        if (arrow) {
            arrow.style.transform = 'rotate(180deg)';
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
        
        // Final check - verify dropdown is visible
        setTimeout(() => {
            const rect = dropdown.getBoundingClientRect();
            const computedStyle = window.getComputedStyle(dropdown);
            console.log('openLanguageDropdown: Final state check', {
                display: computedStyle.display,
                visibility: computedStyle.visibility,
                opacity: computedStyle.opacity,
                position: computedStyle.position,
                zIndex: computedStyle.zIndex,
                width: rect.width,
                height: rect.height,
                top: rect.top,
                left: rect.left,
                right: rect.right,
                bottom: rect.bottom,
                viewportWidth: window.innerWidth,
                viewportHeight: window.innerHeight,
                isVisible: rect.width > 0 && rect.height > 0 && computedStyle.display !== 'none',
                isOnScreen: rect.top >= 0 && rect.left >= 0 && rect.right <= window.innerWidth && rect.bottom <= window.innerHeight,
                inlineStyles: {
                    display: dropdown.style.display,
                    visibility: dropdown.style.visibility,
                    opacity: dropdown.style.opacity,
                    position: dropdown.style.position,
                    left: dropdown.style.left,
                    right: dropdown.style.right,
                    top: dropdown.style.top,
                    width: dropdown.style.width,
                    zIndex: dropdown.style.zIndex
                }
            });
            
            // If dropdown has zero dimensions or is off-screen, try to fix it
            if (rect.width === 0 || rect.height === 0 || (!rect.top && !rect.bottom && !rect.left && !rect.right)) {
                console.error('openLanguageDropdown: Dropdown has invalid dimensions or position!', {
                    rect,
                    computedStyle: {
                        display: computedStyle.display,
                        visibility: computedStyle.visibility,
                        position: computedStyle.position
                    }
                });
                
                // Emergency fallback positioning
                const toggleRect = toggle.getBoundingClientRect();
                dropdown.style.setProperty('left', `${Math.max(12, toggleRect.left - 300)}px`, 'important');
                dropdown.style.setProperty('top', `${toggleRect.top}px`, 'important');
                dropdown.style.setProperty('width', '288px', 'important');
                dropdown.style.setProperty('height', 'auto', 'important');
                console.log('openLanguageDropdown: Applied emergency fallback positioning', {
                    left: dropdown.style.left,
                    top: dropdown.style.top,
                    width: dropdown.style.width
                });
            }
        }, 100);
    }
    
    // Helper function to position language dropdown
    function positionLanguageDropdown(dropdown, toggleRect, userMenuRect, dropdownWidth, margin, viewportWidth) {
        console.log('positionLanguageDropdown: Called', {
            toggleRect: { left: toggleRect.left, top: toggleRect.top, width: toggleRect.width, height: toggleRect.height },
            userMenuRect: { left: userMenuRect.left, top: userMenuRect.top, width: userMenuRect.width, height: userMenuRect.height },
            dropdownWidth,
            viewportWidth
        });
        
        // Ensure dropdown is visible first (use setProperty with important to override Tailwind hidden)
        dropdown.classList.remove('hidden'); // Remove hidden class first
        dropdown.style.setProperty('display', 'block', 'important');
        dropdown.style.setProperty('visibility', 'visible', 'important');
        dropdown.style.setProperty('opacity', '1', 'important');
        dropdown.style.setProperty('position', 'fixed', 'important');
        dropdown.style.setProperty('z-index', '9999', 'important'); // Very high z-index
        
        // Position dropdown to the LEFT of user menu (as a dropside)
        // Calculate left position: user menu left edge minus dropdown width minus margin
        let leftPosition = userMenuRect.left - dropdownWidth - margin;
        
        console.log('positionLanguageDropdown: Calculating position', {
            userMenuLeft: userMenuRect.left,
            userMenuRight: userMenuRect.right,
            dropdownWidth,
            margin,
            viewportWidth,
            calculatedLeftPosition: leftPosition
        });
        
        // Check if dropdown would go off-screen to the left
        if (leftPosition < margin) {
            // Not enough space on left, try positioning to the right instead
            let rightPosition = userMenuRect.right + margin;
            
            console.log('positionLanguageDropdown: Not enough space on left, trying right', {
                rightPosition,
                wouldFit: rightPosition + dropdownWidth <= viewportWidth - margin
            });
            
            // If dropdown would go off-screen to the right, position from right edge
            if (rightPosition + dropdownWidth > viewportWidth - margin) {
                dropdown.style.setProperty('right', `${margin}px`, 'important');
                dropdown.style.setProperty('left', 'auto', 'important');
                console.log('positionLanguageDropdown: Positioned from right edge', { right: margin });
            } else {
                // Position to the right of user menu
                dropdown.style.setProperty('left', `${rightPosition}px`, 'important');
                dropdown.style.setProperty('right', 'auto', 'important');
                console.log('positionLanguageDropdown: Positioned to the right of user menu', { left: rightPosition });
            }
        } else {
            // Position to the left of user menu (preferred position)
            // But first check if this position makes sense (not way off screen)
            if (leftPosition > viewportWidth) {
                console.warn('positionLanguageDropdown: Calculated left position is way off screen, using fallback', {
                    leftPosition,
                    viewportWidth
                });
                // Fallback: position from right edge
                dropdown.style.setProperty('right', `${margin}px`, 'important');
                dropdown.style.setProperty('left', 'auto', 'important');
            } else {
                dropdown.style.setProperty('left', `${leftPosition}px`, 'important');
                dropdown.style.setProperty('right', 'auto', 'important');
                console.log('positionLanguageDropdown: Positioned to the left of user menu', { left: leftPosition });
            }
        }
        
        // Align dropdown top with toggle top (vertical alignment with the Language option)
        dropdown.style.setProperty('top', `${toggleRect.top}px`, 'important');
        dropdown.style.setProperty('width', `${dropdownWidth}px`, 'important');
        dropdown.style.setProperty('max-height', '80vh', 'important');
        dropdown.style.setProperty('overflow-y', 'auto', 'important');
        
        console.log('positionLanguageDropdown: Final styles applied', {
            left: dropdown.style.left,
            right: dropdown.style.right,
            top: dropdown.style.top,
            width: dropdown.style.width,
            position: dropdown.style.position
        });
    }
    
    // Also update position on window resize if dropdown is open
    window.addEventListener('resize', () => {
        const openDropdown = document.querySelector('[id*="language-dropdown"]:not(.hidden)');
        if (openDropdown) {
            const toggleId = openDropdown.id.replace('language-dropdown', 'language-toggle') ||
                           openDropdown.id.replace('user-menu-language-dropdown', 'user-menu-language-toggle') ||
                           openDropdown.id.replace('regular-user-menu-language-dropdown', 'regular-user-menu-language-toggle');
            const toggle = document.getElementById(toggleId);
            const userMenuDropdown = document.getElementById('user-dropdown');
            if (toggle && userMenuDropdown?.contains(toggle)) {
                const toggleRect = toggle.getBoundingClientRect();
                const userMenuRect = userMenuDropdown.getBoundingClientRect();
                const dropdownWidth = 288;
                const margin = 12;
                const viewportWidth = window.innerWidth;
                
                if (toggleRect.width > 0 && userMenuRect.width > 0) {
                    positionLanguageDropdown(openDropdown, toggleRect, userMenuRect, dropdownWidth, margin, viewportWidth);
                }
            }
        }
    });

    function closeLanguageDropdown(toggle, dropdown, arrow) {
        if (!dropdown) return;
        
        // If dropdown was moved to body, restore it to original parent
        if (dropdown.dataset.originalParent && dropdown.parentElement === document.body) {
            // Try to find original parent by ID first
            let originalParent = document.getElementById(dropdown.dataset.originalParent);
            
            // If not found by ID, try to find by class or tag
            if (!originalParent && toggle) {
                const languageContainer = toggle.closest('.relative, .group\\/language');
                if (languageContainer) {
                    originalParent = languageContainer;
                }
            }
            
            if (originalParent) {
                originalParent.appendChild(dropdown);
                console.log('closeLanguageDropdown: Restored dropdown to original parent', {
                    parent: originalParent.tagName + (originalParent.id ? '#' + originalParent.id : '')
                });
            }
            delete dropdown.dataset.originalParent;
            delete dropdown.dataset.originalParentElement;
        }
        
        dropdown.classList.add('hidden');
        // Remove inline styles to allow Tailwind hidden class to work
        dropdown.style.removeProperty('display');
        dropdown.style.removeProperty('visibility');
        dropdown.style.removeProperty('opacity');
        dropdown.style.removeProperty('position');
        dropdown.style.removeProperty('left');
        dropdown.style.removeProperty('right');
        dropdown.style.removeProperty('top');
        dropdown.style.removeProperty('width');
        dropdown.style.removeProperty('z-index');
        dropdown.style.removeProperty('max-height');
        dropdown.style.removeProperty('overflow-y');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }
}

// Close all language dropdowns except the one specified
function closeAllLanguageDropdowns(excludeId) {
    const allLanguageDropdowns = document.querySelectorAll('[id*="language-dropdown"]');
    allLanguageDropdowns.forEach(langDropdown => {
        if (langDropdown.id !== excludeId) {
            // Close this dropdown
            langDropdown.classList.add('hidden');
            langDropdown.style.visibility = '';
            langDropdown.style.opacity = '';
            
            // Find and reset corresponding arrow
            let arrow = null;
            // Try different arrow ID patterns
            const arrowIdPatterns = [
                langDropdown.id.replace('language-dropdown', 'language-arrow'),
                langDropdown.id.replace('-language-dropdown', '-language-arrow'),
                langDropdown.id.replace('user-menu-language-dropdown', 'user-menu-language-arrow'),
                langDropdown.id.replace('regular-user-menu-language-dropdown', 'regular-user-menu-language-arrow')
            ];
            
            for (const arrowId of arrowIdPatterns) {
                arrow = document.getElementById(arrowId);
                if (arrow) break;
            }
            
            // If not found by ID, try finding it in parent
            if (!arrow) {
                const parent = langDropdown.closest('div');
                if (parent) {
                    arrow = parent.querySelector('[id*="language-arrow"]');
                }
            }
            
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
            
            // Find and reset corresponding toggle
            const toggleIdPatterns = [
                langDropdown.id.replace('language-dropdown', 'language-toggle'),
                langDropdown.id.replace('-language-dropdown', '-language-toggle'),
                langDropdown.id.replace('user-menu-language-dropdown', 'user-menu-language-toggle'),
                langDropdown.id.replace('regular-user-menu-language-dropdown', 'regular-user-menu-language-toggle')
            ];
            
            let toggle = null;
            for (const toggleId of toggleIdPatterns) {
                toggle = document.getElementById(toggleId);
                if (toggle) break;
            }
            
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        }
    });
}

// Initialize all language selectors
function initAllLanguageSelectors() {
    // List of all possible language selector ID combinations
    const selectors = [
        { toggle: 'language-toggle', dropdown: 'language-dropdown', arrow: 'dropdown-arrow' },
        { toggle: 'regular-language-toggle', dropdown: 'regular-language-dropdown', arrow: 'regular-dropdown-arrow' },
        { toggle: 'user-menu-language-toggle', dropdown: 'user-menu-language-dropdown', arrow: 'user-menu-language-arrow' },
        { toggle: 'regular-user-menu-language-toggle', dropdown: 'regular-user-menu-language-dropdown', arrow: 'regular-user-menu-language-arrow' }
    ];
    
    // Initialize each selector that exists in the DOM
    selectors.forEach(selector => {
        if (document.getElementById(selector.toggle) && document.getElementById(selector.dropdown)) {
            initLanguageSelector(selector.toggle, selector.dropdown, selector.arrow);
        }
    });
}

// Initialize mobile menu
function initMobileMenu() {
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('mobile-menu');
    const iconOpen = document.getElementById('menu-icon-open');
    const iconClose = document.getElementById('menu-icon-close');

    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const isOpen = !menu.classList.contains('hidden');

            menu.classList.toggle('hidden');
            if (iconOpen) iconOpen.classList.toggle('hidden');
            if (iconClose) iconClose.classList.toggle('hidden');
            toggle.setAttribute('aria-expanded', !isOpen);
        });
    }
}

// Initialize user menu dropdown
function initUserMenu(toggleId = 'user-menu-toggle', dropdownId = 'user-dropdown', arrowId = 'user-dropdown-arrow') {
    const toggle = document.getElementById(toggleId);
    const dropdown = document.getElementById(dropdownId);
    const arrow = document.getElementById(arrowId);

    if (!toggle || !dropdown) {
        return; // Silently return if elements don't exist
    }

    function openUserMenu() {
        // Close any open language dropdowns in user menu
        const langDropdowns = dropdown.querySelectorAll('[id*="language-dropdown"]');
        langDropdowns.forEach(langDropdown => {
            langDropdown.classList.add('hidden');
        });
        const langArrows = dropdown.querySelectorAll('[id*="language-arrow"]');
        langArrows.forEach(langArrow => {
            langArrow.style.transform = 'rotate(0deg)';
        });
        
        if (dropdown) {
            dropdown.classList.remove('hidden');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
            if (toggle) toggle.setAttribute('aria-expanded', 'true');
        }
    }

    function closeUserMenu() {
        // Also close any language dropdowns when closing user menu
        const langDropdowns = dropdown.querySelectorAll('[id*="language-dropdown"]');
        langDropdowns.forEach(langDropdown => {
            langDropdown.classList.add('hidden');
        });
        const langArrows = dropdown.querySelectorAll('[id*="language-arrow"]');
        langArrows.forEach(langArrow => {
            langArrow.style.transform = 'rotate(0deg)';
        });
        
        if (dropdown) {
            dropdown.classList.add('hidden');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
    }

    // Toggle dropdown on button click
    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isVisible = !dropdown.classList.contains('hidden');

        if (isVisible) {
            closeUserMenu();
        } else {
            openUserMenu();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (dropdown && !dropdown.contains(e.target) && toggle && !toggle.contains(e.target)) {
            // Don't close if clicking on language dropdown or language toggle
            const langDropdown = e.target.closest('[id*="language-dropdown"]');
            const langToggle = e.target.closest('[id*="language-toggle"]');
            
            // Check if clicking inside any language dropdown content
            const isLanguageClick = langDropdown || langToggle || 
                                  e.target.closest('[class*="fi fi-"]') || 
                                  e.target.closest('a[href^="/sr"]') || 
                                  e.target.closest('a[href^="/en"]') ||
                                  e.target.closest('a[href^="/hr"]');
            
            // Only close if not clicking on language selector elements
            if (!isLanguageClick) {
                closeUserMenu();
            }
        }
    });

    // Close dropdown on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && dropdown && !dropdown.classList.contains('hidden')) {
            // First close language dropdown if open
            const langDropdown = dropdown.querySelector('[id*="language-dropdown"]:not(.hidden)');
            if (langDropdown) {
                langDropdown.classList.add('hidden');
                const langArrow = dropdown.querySelector('[id*="language-arrow"]');
                if (langArrow) langArrow.style.transform = 'rotate(0deg)';
            } else {
                closeUserMenu();
            }
        }
    });
}

// Initialize all user menus
function initAllUserMenus() {
    initUserMenu('user-menu-toggle', 'user-dropdown', 'user-dropdown-arrow');
    // Note: regular header uses same IDs, so only one is needed
}

// Initialize everything when DOM is ready
function init() {
    initTheme();
    initStarfield();
    initAllLanguageSelectors();
    initMobileMenu();
    initAllUserMenus();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
