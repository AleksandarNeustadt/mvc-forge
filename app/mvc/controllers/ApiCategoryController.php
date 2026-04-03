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
 * Public API blog category route adapter.
 */
class ApiCategoryController extends ApiController
{
}


if (!\class_exists('ApiCategoryController', false) && !\interface_exists('ApiCategoryController', false) && !\trait_exists('ApiCategoryController', false)) {
    \class_alias(__NAMESPACE__ . '\\ApiCategoryController', 'ApiCategoryController');
}
