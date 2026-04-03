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
 * Public API language route adapter.
 */
class ApiLanguageController extends ApiController
{
}


if (!\class_exists('ApiLanguageController', false) && !\interface_exists('ApiLanguageController', false) && !\trait_exists('ApiLanguageController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiLanguageController', 'ApiLanguageController');
}
