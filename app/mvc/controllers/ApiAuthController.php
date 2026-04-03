<?php

namespace App\Controllers;

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
 * Public API auth route adapter.
 */
class ApiAuthController extends ApiController
{
}


if (!\class_exists('ApiAuthController', false) && !\interface_exists('ApiAuthController', false) && !\trait_exists('ApiAuthController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiAuthController', 'ApiAuthController');
}
