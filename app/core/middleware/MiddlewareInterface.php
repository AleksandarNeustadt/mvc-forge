<?php

namespace App\Core\middleware;


use App\Core\http\Request;use BadMethodCallException;
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

// core/middleware/MiddlewareInterface.php

interface MiddlewareInterface {
    /**
     * Handle incoming request
     *
     * @param Request $request The incoming request
     * @param Closure $next The next middleware in the pipeline
     * @return mixed
     */
    public function handle(Request $request, Closure $next);
}


if (!\class_exists('MiddlewareInterface', false) && !\interface_exists('MiddlewareInterface', false) && !\trait_exists('MiddlewareInterface', false)) {
    \class_alias(__NAMESPACE__ . '\\MiddlewareInterface', 'MiddlewareInterface');
}
