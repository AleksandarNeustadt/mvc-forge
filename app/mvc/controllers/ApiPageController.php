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
 * Public API pages route adapter.
 */
class ApiPageController extends ApiController
{
}


if (!\class_exists('ApiPageController', false) && !\interface_exists('ApiPageController', false) && !\trait_exists('ApiPageController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiPageController', 'ApiPageController');
}
