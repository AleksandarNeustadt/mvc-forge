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
 * Dashboard navigation-menu route adapter.
 */
class DashboardNavigationController extends DashboardController
{
}


if (!\class_exists('DashboardNavigationController', false) && !\interface_exists('DashboardNavigationController', false) && !\trait_exists('DashboardNavigationController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardNavigationController', 'DashboardNavigationController');
}
