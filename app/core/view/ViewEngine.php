<?php

namespace App\Core\view;

use App\Core\logging\Logger;
use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Error;
use ErrorException;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Simple Template Engine
 * 
 * Blade-like template engine, but simpler and faster
 * 
 * Supported directives:
 * - @extends('layout') - Extend a layout
 * - @section('name') ... @endsection - Define a section
 * - @yield('name', 'default') - Output a section with optional default
 * - @include('component') - Include a partial
 * - @component('name', ['var' => 'value']) ... @endcomponent - Component with props
 * - @if, @elseif, @else, @endif - Conditional statements
 * - @foreach, @endforeach - Loop statements
 * - @for, @endfor - For loop
 * - @while, @endwhile - While loop
 * - @php ... @endphp - Raw PHP code
 * - @{{ $var }} - Echo escaped output
 * - {!! $var !!} - Echo raw output (HTML)
 * - @{{-- comment --}} - Comments (removed from output)
 * - @isset($var) ... @endisset - Check if variable is set
 * - @empty($var) ... @endempty - Check if variable is empty
 * - @auth ... @endauth - Check if user is authenticated
 * - @guest ... @endguest - Check if user is guest
 */

class ViewEngine {
    private static string $viewPath = __DIR__ . '/../../mvc/views';
    private static string $cachePath = __DIR__ . '/../../storage/cache/views';
    private static bool $cacheEnabled = true;
    
    /**
     * Get view path (public method for templates to access helpers)
     */
    public static function getViewPath(): string {
        return self::$viewPath;
    }

    /**
     * Compile and render a template
     */
    public static function render(string $template, array $data = []): string {
        // Extract data for template
        extract($data);
        
        // Make $viewPath and $cspNonce available to templates
        $data['_viewPath'] = self::$viewPath;
        $data['cspNonce'] = function_exists('csp_nonce') ? csp_nonce() : '';
        
        // Re-extract after adding new variables
        extract($data);

        // Get full template path
        $templatePath = self::getTemplatePath($template);
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: {$template} ({$templatePath})");
        }

        // Check if we need to compile (if using .template.php extension or cache is disabled)
        $isTemplateFile = str_ends_with($templatePath, '.template.php');
        
        if ($isTemplateFile || !self::$cacheEnabled) {
            $compiled = self::compile(file_get_contents($templatePath), $templatePath);
        } else {
            // Use cached version if available and newer
            $cachedPath = self::getCachedPath($templatePath);
            if (file_exists($cachedPath) && filemtime($cachedPath) >= filemtime($templatePath)) {
                $compiled = file_get_contents($cachedPath);
            } else {
                $compiled = self::compile(file_get_contents($templatePath), $templatePath);
                // Cache the compiled version
                if (self::$cacheEnabled) {
                    self::ensureCacheDirectory();
                    self::writeCacheFile($cachedPath, $compiled);
                }
            }
        }

        // Execute compiled template
        ob_start();
        try {
            // Write compiled code to temp file for better error messages (in debug mode)
            if (is_debug() && defined('APP_DEBUG') && APP_DEBUG) {
                $tempFile = sys_get_temp_dir() . '/template_' . md5($template) . '.php';
                file_put_contents($tempFile, $compiled);
                include $tempFile;
                // Clean up temp file after execution
                @unlink($tempFile);
            } else {
                eval('?>' . $compiled);
            }
        } catch (ParseError $e) {
            // Clear output buffer on error
            ob_end_clean();
            
            // Save compiled code to temp file for inspection
            $tempFile = sys_get_temp_dir() . '/template_error_' . md5($template) . '.php';
            file_put_contents($tempFile, $compiled);
            
            // Log the compiled code for debugging
            $errorMsg = "Template compilation error in: {$template}\n";
            $errorMsg .= "Template path: {$templatePath}\n";
            $errorMsg .= "Parse error: " . $e->getMessage() . "\n";
            $errorMsg .= "Line: " . $e->getLine() . "\n";
            $errorMsg .= "Compiled code saved to: {$tempFile}\n\n";
            $errorMsg .= "First 1000 chars of compiled code:\n";
            $errorMsg .= substr($compiled, 0, 1000) . "...\n";
            
            Logger::error('Template compilation failed', [
                'template' => $template,
                'template_path' => $templatePath,
                'compiled_preview' => substr($compiled, 0, 1000),
                'debug_file' => $tempFile,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
            
            // In debug mode, show more details
            if (function_exists('is_debug') && is_debug()) {
                throw new Exception("Template compilation failed for {$template} ({$templatePath}): " . $e->getMessage() . "\nCompiled code saved to: {$tempFile}\n\nFirst 1000 chars:\n" . substr($compiled, 0, 1000));
            }
            
            // Fallback: try to use original PHP view if exists
            $fallbackPath = str_replace('.template.php', '.php', $templatePath);
            if (!file_exists($fallbackPath)) {
                // Try finding it in pages directory
                $fallbackPath = __DIR__ . '/../../mvc/views/pages/' . str_replace('pages/', '', $template) . '.php';
            }
            
            if (file_exists($fallbackPath)) {
                extract($data);
                include $fallbackPath;
                return ob_get_clean();
            }
            
            throw new Exception("Template compilation failed and no fallback available for {$template} ({$templatePath}). Compiled code saved to: {$tempFile}");
        } catch (Error $e) {
            ob_end_clean();
            Logger::error('Template execution failed', [
                'template' => $template,
                'template_path' => $templatePath,
                'compiled_file' => $e->getFile(),
                'line' => $e->getLine(),
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException(
                "Template execution failed for {$template} ({$templatePath}): {$e->getMessage()}",
                0,
                $e
            );
        }
        return ob_get_clean();
    }

    /**
     * Get template path
     */
    private static function getTemplatePath(string $template): string {
        // Check for .template.php first (new template format)
        $templatePath = self::$viewPath . '/' . str_replace('.', '/', $template) . '.template.php';
        if (file_exists($templatePath)) {
            return $templatePath;
        }

        // Fallback to .php (regular PHP views)
        $templatePath = self::$viewPath . '/' . str_replace('.', '/', $template) . '.php';
        return $templatePath;
    }

    /**
     * Get cached template path
     */
    private static function getCachedPath(string $templatePath): string {
        $hash = md5($templatePath);
        return self::$cachePath . '/' . $hash . '.php';
    }

    /**
     * Ensure cache directory exists
     */
    private static function ensureCacheDirectory(): void {
        if (!is_dir(self::$cachePath)) {
            mkdir(self::$cachePath, 0755, true);
        }
    }

    private static function writeCacheFile(string $cachedPath, string $compiled): void
    {
        $tempPath = $cachedPath . '.tmp.' . bin2hex(random_bytes(8));

        try {
            if (file_put_contents($tempPath, $compiled, LOCK_EX) === false) {
                throw new RuntimeException("Failed to write temporary view cache file: {$tempPath}");
            }

            if (!rename($tempPath, $cachedPath)) {
                throw new RuntimeException("Failed to promote temporary view cache file to {$cachedPath}");
            }
        } catch (Throwable $exception) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            throw $exception;
        }
    }

    /**
     * Compile template to PHP
     */
    private static function compile(string $content, string $templatePath): string {
        // Important: Order matters!
        // 1. First handle @extends (needs to process whole template structure)
        $content = self::compileExtends($content, dirname($templatePath));
        
        // 2. Remove comments early (no need to process them)
        $content = self::compileComments($content);
        
        // 3. Compile @php blocks first (they might contain variables used later)
        // Use greedy matching with DOTALL flag to capture multi-line blocks
        // Note: (.*?) is non-greedy but with /s flag, it will match across lines
        $templateDir = dirname($templatePath);
        $content = preg_replace_callback(
            '/@php\s*(.*?)\s*@endphp/s',
            function ($matches) use ($templateDir) {
                $phpCode = $matches[1];
                // Trim only leading/trailing whitespace, preserve internal whitespace
                $phpCode = preg_replace('/^\s+/', '', $phpCode); // Remove leading whitespace
                $phpCode = preg_replace('/\s+$/', '', $phpCode); // Remove trailing whitespace
                
                // Don't add extra PHP tags if code is empty
                if (empty(trim($phpCode))) {
                    return '';
                }
                
                // Replace __DIR__ with actual template directory path
                // This ensures require_once and similar file path operations work correctly in compiled templates
                // In compiled templates executed via eval(), __DIR__ would point to ViewEngine location, not the original template
                // So we replace it with the actual template directory path (absolute path)
                // Use realpath to get absolute path, with fallback to project root calculation
                $actualTemplateDir = realpath($templateDir);
                if ($actualTemplateDir === false) {
                    // If realpath fails, calculate absolute path from project root
                    // ViewEngine is at core/view/, so project root is 2 levels up
                    $projectRoot = realpath(__DIR__ . '/../..');
                    if ($projectRoot !== false) {
                        // Make templateDir relative to project root if it's already relative
                        if (!str_starts_with($templateDir, '/')) {
                            $actualTemplateDir = $projectRoot . '/' . $templateDir;
                        } else {
                            // Already absolute, use as-is
                            $actualTemplateDir = $templateDir;
                        }
                        // Try realpath again on constructed path
                        $actualTemplateDir = realpath($actualTemplateDir) ?: $actualTemplateDir;
                    } else {
                        // Last resort: use original path as-is
                        $actualTemplateDir = $templateDir;
                    }
                }
                $escapedTemplateDir = addslashes($actualTemplateDir);
                
                // Replace __DIR__ with actual template directory path
                // Match __DIR__ as a whole word (using word boundaries) to avoid partial matches
                // This handles patterns like: require_once __DIR__ . '/path/to/file.php'
                // We need to be careful with escaping - __DIR__ is a magic constant, so we replace it with string literal
                $phpCode = preg_replace(
                    '/\b__DIR__\b/',
                    "'" . $escapedTemplateDir . "'",
                    $phpCode
                );
                
                return '<?php ' . $phpCode . ' ?>';
            },
            $content
        );
        
        // 4. Compile echo statements (before directives to avoid conflicts)
        $content = self::compileEchos($content);
        
        // 5. Compile control directives (@if, @foreach, etc.)
        $content = self::compileDirectives($content);
        
        // 6. Compile @include (needs to be after directives to avoid conflicts)
        $content = self::compileIncludes($content, dirname($templatePath));
        
        // 7. Compile @component (after includes)
        $content = self::compileComponents($content);
        
        // 8. Handle sections and yields (if not already handled by extends)
        $content = self::compileSections($content);
        $content = self::compileYields($content);
        
        return $content;
    }

    /**
     * Compile @extends directive
     */
    private static function compileExtends(string $content, string $baseDir): string {
        // Match @extends('layout') or @extends("layout")
        if (preg_match('/@extends\([\'"](.+?)[\'"]\)/', $content, $matches)) {
            $layoutName = $matches[1];
            
            // Try to find layout file
            // First try relative to current directory
            $layoutPath = $baseDir . '/' . str_replace('.', '/', $layoutName) . '.template.php';
            if (!file_exists($layoutPath)) {
                $layoutPath = $baseDir . '/' . str_replace('.', '/', $layoutName) . '.php';
            }
            
            // If not found, try from view root/layouts
            if (!file_exists($layoutPath)) {
                $layoutPath = self::$viewPath . '/layouts/' . str_replace('.', '/', $layoutName) . '.template.php';
                if (!file_exists($layoutPath)) {
                    $layoutPath = self::$viewPath . '/layouts/' . str_replace('.', '/', $layoutName) . '.php';
                }
            }
            
            // If still not found, try directly from view root
            if (!file_exists($layoutPath)) {
                $layoutPath = self::$viewPath . '/' . str_replace('.', '/', $layoutName) . '.template.php';
                if (!file_exists($layoutPath)) {
                    $layoutPath = self::$viewPath . '/' . str_replace('.', '/', $layoutName) . '.php';
                }
            }
            
            if (file_exists($layoutPath)) {
                $layoutContent = file_get_contents($layoutPath);
                // Compile layout first to handle nested extends
                $layoutContent = self::compile($layoutContent, $layoutPath);
                // Remove @extends directive from child
                $childContent = preg_replace('/@extends\([\'"](.+?)[\'"]\)\s*/', '', $content);
                // Replace @yield in layout with child content
                $content = self::replaceYieldsInLayout($layoutContent, $childContent);
                return $content;
            }
        }
        
        return $content;
    }

    /**
     * Replace @yield directives in layout with section content
     */
    private static function replaceYieldsInLayout(string $layout, string $child): string {
        // Extract all sections from child
        $sections = [];
        if (preg_match_all('/@section\([\'"](.+?)[\'"]\)(.*?)@endsection/s', $child, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sectionName = $match[1];
                $sectionContent = trim($match[2]);
                $sections[$sectionName] = $sectionContent;
            }
        }

        // Replace @yield with section content
        $layout = preg_replace_callback(
            '/@yield\([\'"](.+?)[\'"](?:,\s*[\'"](.+?)[\'"])?\)/',
            function ($matches) use ($sections) {
                $sectionName = $matches[1];
                $default = $matches[2] ?? null;
                return $sections[$sectionName] ?? ($default ?: '');
            },
            $layout
        );

        return $layout;
    }

    /**
     * Compile @include directives
     */
    private static function compileIncludes(string $content, string $baseDir): string {
        return preg_replace_callback(
            '/@include\([\'"](.+?)[\'"](?:,\s*(.+?))?\)/',
            function ($matches) use ($baseDir) {
                $includeName = $matches[1];
                $vars = isset($matches[2]) ? trim($matches[2]) : '[]';
                
                // Try to find the include file
                // First try relative to current directory
                $includePath = $baseDir . '/' . str_replace('.', '/', $includeName) . '.template.php';
                if (!file_exists($includePath)) {
                    $includePath = $baseDir . '/' . str_replace('.', '/', $includeName) . '.php';
                }
                
                // If not found, try from view root
                if (!file_exists($includePath)) {
                    $includePath = self::$viewPath . '/' . str_replace('.', '/', $includeName) . '.template.php';
                    if (!file_exists($includePath)) {
                        $includePath = self::$viewPath . '/' . str_replace('.', '/', $includeName) . '.php';
                    }
                }
                
                if (file_exists($includePath)) {
                    $includeContent = file_get_contents($includePath);
                    $compiledInclude = self::compile($includeContent, $includePath);
                    // Wrap in output buffer to capture and return
                    return "<?php extract({$vars}); ?>" . $compiledInclude;
                }
                
                return "<?php /* Include not found: {$includeName} */ ?>";
            },
            $content
        );
    }

    /**
     * Compile @component directives
     */
    private static function compileComponents(string $content): string {
        // Match @component('name', ['var' => 'value']) ... @endcomponent
        $content = preg_replace_callback(
            '/@component\([\'"](.+?)[\'"](?:,\s*(.+?))?\)(.*?)@endcomponent/s',
            function ($matches) {
                $componentName = $matches[1];
                $props = isset($matches[2]) ? trim($matches[2]) : '[]';
                $slot = $matches[3];
                
                // Try to find component file
                $componentPath = self::$viewPath . '/components/' . str_replace('.', '/', $componentName) . '.template.php';
                if (!file_exists($componentPath)) {
                    $componentPath = self::$viewPath . '/components/' . str_replace('.', '/', $componentName) . '.php';
                }
                
                if (file_exists($componentPath)) {
                    $componentContent = file_get_contents($componentPath);
                    $compiledComponent = self::compile($componentContent, $componentPath);
                    
                    // Replace {{ $slot }} in component with slot content
                    $compiledComponent = str_replace('{{ $slot }}', $slot, $compiledComponent);
                    
                    return "<?php extract({$props}); ?>" . $compiledComponent . "<?php ";
                }
                
                return "<?php /* Component not found: {$componentName} */ ?>";
            },
            $content
        );

        return $content;
    }

    /**
     * Compile @section and @endsection
     */
    private static function compileSections(string $content): string {
        // Sections are handled in compileExtends, but we still need to remove them from standalone templates
        // Actually, we'll leave them as they're useful for manual section management
        return $content;
    }

    /**
     * Compile @yield directives (standalone, not in extends context)
     */
    private static function compileYields(string $content): string {
        // Yields are handled in compileExtends, but for standalone use:
        return preg_replace(
            '/@yield\([\'"](.+?)[\'"](?:,\s*[\'"](.+?)[\'"])?\)/',
            '<?php echo isset($sections[\'$1\']) ? $sections[\'$1\'] : (isset($2) ? $2 : \'\'); ?>',
            $content
        );
    }

    /**
     * Compile echo statements: {{ }} and {!! !!}
     */
    private static function compileEchos(string $content): string {
        // Compile {!! $var !!} - raw output (no escaping)
        $content = preg_replace(
            '/\{!!\s*(.+?)\s*!!\}/s',
            '<?php echo $1; ?>',
            $content
        );

        // Compile {{ $var }} - escaped output
        // Support helper functions like __(), renderBreadcrumb(), etc.
        $content = preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/s',
            function ($matches) {
                $expr = trim($matches[1]);
                
                // Don't double-escape if already wrapped
                if (strpos($expr, 'htmlspecialchars') !== false || 
                    strpos($expr, 'htmlentities') !== false ||
                    strpos($expr, 'e(') !== false) {
                    return '<?php echo ' . $expr . '; ?>';
                }
                
                // Helper functions that already return safe HTML or don't need escaping
                $noEscapeFunctions = [
                    '__', 'route', 'csrf_field', 'csrf_token', 'current_lang',
                    'renderBreadcrumb', 'get_flag_code', 'csp_nonce'
                ];
                
                $needsEscaping = true;
                foreach ($noEscapeFunctions as $func) {
                    // Check if expression starts with function call
                    if (preg_match('/^' . preg_quote($func) . '\s*\(/', $expr)) {
                        $needsEscaping = false;
                        break;
                    }
                }
                
                // Special case: __() function might return HTML in some cases
                // But usually it's safe to escape translations
                if (preg_match('/^__\s*\(/', $expr)) {
                    // Escape translation strings by default (safer)
                    return '<?php echo htmlspecialchars(' . $expr . ', ENT_QUOTES, \'UTF-8\'); ?>';
                }
                
                if ($needsEscaping) {
                    return '<?php echo htmlspecialchars(' . $expr . ', ENT_QUOTES, \'UTF-8\'); ?>';
                } else {
                    return '<?php echo ' . $expr . '; ?>';
                }
            },
            $content
        );

        // Compile @{{ $var }} - escaped output (alternative syntax)
        $content = preg_replace(
            '/@\{\{\s*(.+?)\s*\}\}/s',
            '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>',
            $content
        );

        return $content;
    }

    /**
     * Helper to find matching closing parenthesis
     */
    private static function findBalancedParens(string $text, int $startPos): int|false {
        $depth = 0;
        for ($i = $startPos; $i < strlen($text); $i++) {
            if ($text[$i] === '(') {
                $depth++;
            } elseif ($text[$i] === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i; // Return position of matching closing parenthesis
                }
            }
        }
        return false; // No matching parenthesis found
    }
    
    /**
     * Compile a directive with balanced parentheses
     */
    private static function compileDirectiveWithParens(string $content, string $directive, string $replacement): string {
        $pattern = '/' . preg_quote($directive, '/') . '\s*\(/';
        
        while (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $startPos = $matches[0][1];
            // Find the position of the opening parenthesis '('
            $matchLength = strlen($matches[0][0]);
            $parenPos = $startPos + $matchLength - 1; // Position of '(' character
            
            // Find matching closing parenthesis
            $endPos = self::findBalancedParens($content, $parenPos);
            if ($endPos !== false) {
                // Extract condition/expression (without the parentheses)
                $expr = substr($content, $parenPos + 1, $endPos - $parenPos - 1);
                // Replace entire directive including @if and parentheses
                $replaced = str_replace('{expr}', $expr, $replacement);
                // Replace from start of directive (@if) to end of closing parenthesis
                $content = substr_replace($content, $replaced, $startPos, $endPos + 1 - $startPos);
            } else {
                // No matching parenthesis found, skip this occurrence
                // Move past this directive to avoid infinite loop
                $content = substr_replace($content, '', $startPos, $matchLength);
            }
        }
        
        return $content;
    }

    /**
     * Compile control directives (@if, @foreach, etc.)
     */
    private static function compileDirectives(string $content): string {
        // @if ... @elseif ... @else ... @endif
        $content = self::compileDirectiveWithParens($content, '@if', '<?php if ({expr}): ?>');
        $content = self::compileDirectiveWithParens($content, '@elseif', '<?php elseif ({expr}): ?>');
        $content = preg_replace('/@else\b/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif\b/', '<?php endif; ?>', $content);

        // @foreach ... @endforeach (supports both "as $value" and "as $key => $value")
        $content = self::compileDirectiveWithParens($content, '@foreach', '<?php foreach ({expr}): ?>');
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);

        // @for ... @endfor
        $content = self::compileDirectiveWithParens($content, '@for', '<?php for ({expr}): ?>');
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);

        // @while ... @endwhile
        $content = self::compileDirectiveWithParens($content, '@while', '<?php while ({expr}): ?>');
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);

        // @isset ... @endisset
        $content = self::compileDirectiveWithParens($content, '@isset', '<?php if (isset({expr})): ?>');
        $content = preg_replace('/@endisset/', '<?php endif; ?>', $content);

        // @empty ... @endempty
        $content = self::compileDirectiveWithParens($content, '@empty', '<?php if (empty({expr})): ?>');
        $content = preg_replace('/@endempty/', '<?php endif; ?>', $content);

        // @auth ... @endauth (check if user is authenticated)
        $content = preg_replace('/@auth/', '<?php if (isset($user) && $user): ?>', $content);
        $content = preg_replace('/@endauth/', '<?php endif; ?>', $content);

        // @guest ... @endguest (check if user is guest)
        $content = preg_replace('/@guest/', '<?php if (!isset($user) || !$user): ?>', $content);
        $content = preg_replace('/@endguest/', '<?php endif; ?>', $content);

        // Note: @php blocks are handled earlier in compile() method
        
        return $content;
    }

    /**
     * Remove comments: {{-- --}}
     */
    private static function compileComments(string $content): string {
        return preg_replace('/\{\{--\s*(.*?)\s*--\}\}/s', '', $content);
    }

    /**
     * Clear view cache
     */
    public static function clearCache(): void {
        if (is_dir(self::$cachePath)) {
            $files = glob(self::$cachePath . '/*.php');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Set cache enabled/disabled
     */
    public static function setCacheEnabled(bool $enabled): void {
        self::$cacheEnabled = $enabled;
    }

    /**
     * Set view path
     */
    public static function setViewPath(string $path): void {
        self::$viewPath = $path;
    }

    /**
     * Set cache path
     */
    public static function setCachePath(string $path): void {
        self::$cachePath = $path;
    }
}


if (!\class_exists('ViewEngine', false) && !\interface_exists('ViewEngine', false) && !\trait_exists('ViewEngine', false)) {
    \class_alias(__NAMESPACE__ . '\\ViewEngine', 'ViewEngine');
}
