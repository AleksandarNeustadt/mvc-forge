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
 * Dashboard page-management route adapter.
 */
class DashboardPageController extends DashboardController
{
}


if (!\class_exists('DashboardPageController', false) && !\interface_exists('DashboardPageController', false) && !\trait_exists('DashboardPageController', false)) {
    \class_alias(__NAMESPACE__ . '\\DashboardPageController', 'DashboardPageController');
}
