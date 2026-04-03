<?php
/**
 * Resolve application root (folder that contains core/, mvc/, .env).
 * Default: sibling "app" next to this directory (public_html).
 * Override: APP_ROOT_OVERRIDE = absolute path to app/ (SetEnv / php-fpm / shell).
 */
declare(strict_types=1);

if (!function_exists('ap_resolve_app_path')) {
    function ap_resolve_app_path(): string
    {
        $override = $_SERVER['APP_ROOT_OVERRIDE'] ?? $_ENV['APP_ROOT_OVERRIDE'] ?? '';
        if ($override === '' && function_exists('getenv')) {
            $g = getenv('APP_ROOT_OVERRIDE');
            if ($g !== false && $g !== '') {
                $override = $g;
            }
        }
        $normalize = static function (string $path): string {
            $path = rtrim(str_replace('\\', '/', $path), '/');
            $realPath = realpath($path);

            return $realPath !== false ? str_replace('\\', '/', $realPath) : $path;
        };

        $isValidAppPath = static function (string $path): bool {
            return $path !== '' && is_file(rtrim($path, '/') . '/core/config/Env.php');
        };

        $override = $normalize((string) $override);
        if ($isValidAppPath($override)) {
            return $override;
        }

        $candidates = [];
        $addCandidate = static function (?string $path) use (&$candidates, $normalize): void {
            if (!is_string($path) || trim($path) === '') {
                return;
            }

            $normalized = $normalize($path);
            if (!in_array($normalized, $candidates, true)) {
                $candidates[] = $normalized;
            }
        };

        $scriptDir = $normalize(__DIR__);
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $normalize((string) $_SERVER['DOCUMENT_ROOT']) : '';
        $scriptFilename = isset($_SERVER['SCRIPT_FILENAME']) ? $normalize(dirname((string) $_SERVER['SCRIPT_FILENAME'])) : '';

        $addCandidate($scriptDir . '/app');
        $addCandidate(dirname($scriptDir) . '/app');
        $addCandidate(dirname(dirname($scriptDir)) . '/app');

        if ($documentRoot !== '') {
            $addCandidate($documentRoot . '/app');
            $addCandidate(dirname($documentRoot) . '/app');
            $addCandidate(dirname(dirname($documentRoot)) . '/app');
        }

        if ($scriptFilename !== '') {
            $addCandidate($scriptFilename . '/app');
            $addCandidate(dirname($scriptFilename) . '/app');
            $addCandidate(dirname(dirname($scriptFilename)) . '/app');
        }

        foreach ($candidates as $candidate) {
            if ($isValidAppPath($candidate)) {
                return $candidate;
            }
        }

        return $normalize(dirname(__DIR__) . '/app');
    }
}
