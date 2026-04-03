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
 * Public API blog post route adapter.
 */
class ApiPostController extends ApiController
{
}


if (!\class_exists('ApiPostController', false) && !\interface_exists('ApiPostController', false) && !\trait_exists('ApiPostController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiPostController', 'ApiPostController');
}
