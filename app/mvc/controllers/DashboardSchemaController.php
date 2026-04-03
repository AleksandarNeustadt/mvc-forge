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
 * Database/schema management route adapter for dashboard routes.
 */
class DashboardSchemaController extends DashboardController
{
}


if (!\class_exists('DashboardSchemaController', false) && !\interface_exists('DashboardSchemaController', false) && !\trait_exists('DashboardSchemaController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardSchemaController', 'DashboardSchemaController');
}
