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
 * Dashboard home and analytics route adapter.
 *
 * Keeps top-level dashboard routes separated from resource CRUD routes while
 * preserving current behaviour through the shared DashboardController logic.
 */
class DashboardHomeController extends DashboardController
{
}


if (!\class_exists('DashboardHomeController', false) && !\interface_exists('DashboardHomeController', false) && !\trait_exists('DashboardHomeController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardHomeController', 'DashboardHomeController');
}
