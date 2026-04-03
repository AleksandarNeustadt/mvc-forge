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
 * Public API blog tag route adapter.
 */
class ApiTagController extends ApiController
{
}


if (!\class_exists('ApiTagController', false) && !\interface_exists('ApiTagController', false) && !\trait_exists('ApiTagController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiTagController', 'ApiTagController');
}
