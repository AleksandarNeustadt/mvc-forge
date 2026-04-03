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
 * Public API navigation menu route adapter.
 */
class ApiMenuController extends ApiController
{
}


if (!\class_exists('ApiMenuController', false) && !\interface_exists('ApiMenuController', false) && !\trait_exists('ApiMenuController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiMenuController', 'ApiMenuController');
}
